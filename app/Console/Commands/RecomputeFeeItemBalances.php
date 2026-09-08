<?php

namespace App\Console\Commands;

use App\Models\StudentFeeItem;
use Illuminate\Console\Command;

/**
 * Read-only audit (and optional --fix) for StudentFeeItem.amount_paid/
 * balance/status drifting away from the true sum of its own
 * PaymentAllocation rows. Confirmed real-world case (Samantha Mbithi's
 * GLA2 Tuition Fee, item #847, Sep 2026): amount_paid stored as 25,000
 * (full, status=paid) but its two real PaymentAllocation rows only sum to
 * 23,500 — silently hiding a genuine 1,500 still owed, and making the fee
 * item invisible to any future payment allocation (both the FIFO engine
 * and the payments modal's dropdown correctly exclude anything already
 * marked balance=0/paid).
 *
 * Deliberately scoped to items with at least ONE real PaymentAllocation
 * row — items with zero allocations but amount_paid > 0 are typically
 * legitimate historical-import records (finance:import-historical-fees),
 * money received before this system existed with no formal allocation
 * trail. Recomputing those from allocations would wrongly zero them out.
 */
class RecomputeFeeItemBalances extends Command
{
    protected $signature = 'finance:recompute-fee-item-balances
        {--fix : Actually correct drifted items. Without this flag, only previews.}';

    protected $description = 'Audit (and optionally fix) StudentFeeItem.amount_paid/balance drift from real PaymentAllocation sums';

    public function handle(): int
    {
        // Discount/credit items (negative amount) are never paid via a real
        // PaymentAllocation — they carry their own permanent negative
        // balance by design. Excluding them defensively even though
        // whereHas('allocations') should already do so in practice.
        $items = StudentFeeItem::query()->whereHas('allocations')->where('amount', '>=', 0)->get();

        $mismatches = collect();

        foreach ($items as $item) {
            $realPaid = (float) $item->allocations()->sum('amount_allocated');
            $storedPaid = (float) $item->amount_paid;

            if (abs($realPaid - $storedPaid) >= 0.01) {
                $mismatches->push([$item, $realPaid, $storedPaid]);
            }
        }

        if ($mismatches->isEmpty()) {
            $this->info('No drift found — every fee item with real allocations matches its stored amount_paid.');
            return self::SUCCESS;
        }

        $rows = $mismatches->map(function ($m) {
            [$item, $realPaid, $storedPaid] = $m;
            $direction = $storedPaid > $realPaid ? 'OVERSTATED (hides real debt)' : 'UNDERSTATED (risks double payment)';
            return [
                $item->id,
                $item->description,
                number_format((float) $item->amount, 2),
                number_format($storedPaid, 2),
                number_format($realPaid, 2),
                $item->status,
                $direction,
            ];
        });

        $this->table(['Item #', 'Description', 'Amount', 'Stored paid', 'Real paid', 'Status', 'Direction'], $rows);
        $this->warn("{$mismatches->count()} fee item(s) drifted from their real allocation total.");

        if (!$this->option('fix')) {
            $this->warn('DRY RUN — nothing was changed. Re-run with --fix to recompute amount_paid/balance/status from the real allocation sums.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Recompute {$mismatches->count()} fee item(s) from their real allocation totals?", false)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        foreach ($mismatches as [$item, $realPaid]) {
            $newBalance = max(0, (float) $item->amount - $realPaid);
            $item->update([
                'amount_paid' => $realPaid,
                'balance' => $newBalance,
                'status' => $newBalance <= 0 ? 'paid' : ($realPaid > 0 ? 'partial' : 'pending'),
            ]);
        }

        $this->info("Recomputed {$mismatches->count()} fee item(s).");

        return self::SUCCESS;
    }
}
