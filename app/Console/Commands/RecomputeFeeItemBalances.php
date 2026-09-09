<?php

namespace App\Console\Commands;

use App\Models\StudentFeeItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit (and optional --fix) for StudentFeeItem.amount_paid/
 * balance/status drifting away from the true sum of its own
 * PaymentAllocation rows. Confirmed real-world cases (Samantha Mbithi, Sep
 * 2026): item #847 (GLA2 Tuition) stored amount_paid=25,000/paid but its
 * real PaymentAllocation rows only sum to 23,500 — hiding a genuine 1,500
 * still owed; item #683 (GRBC Tuition) stored amount_paid=12,500/paid with
 * ZERO real PaymentAllocation rows at all — no legitimate code path
 * (checked FeeItemAdjustmentService — its own fee_item_audits trail is
 * empty for both items — and finance:import-historical-fees, which always
 * creates new positive charges as pending/amount_paid=0) explains that
 * state, so it isn't a legitimate historical-import artifact either. Both
 * patterns make the fee item invisible to future payment allocation (the
 * FIFO engine and the payments modal's dropdown both correctly exclude
 * anything already marked balance=0) while the real amount owed is
 * silently hidden from the dashboard/Fee Schedule (though it still
 * correctly surfaces on the statement PDF, which derives everything from
 * real allocations independently — hence statement/dashboard disagreeing).
 *
 * Excludes discounts/credits (negative amount) and waived items
 * (status=waived or credit_type=waiver) — both legitimately reach a
 * zero/negative balance without any real PaymentAllocation, by design.
 */
class RecomputeFeeItemBalances extends Command
{
    protected $signature = 'finance:recompute-fee-item-balances
        {--fix : Actually correct drifted items. Without this flag, only previews.}';

    protected $description = 'Audit (and optionally fix) StudentFeeItem.amount_paid/balance drift from real PaymentAllocation sums';

    public function handle(): int
    {
        $items = StudentFeeItem::query()
            ->where('amount', '>=', 0)
            ->where('status', '!=', 'waived')
            ->whereNull('credit_type')
            ->get();

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

        DB::transaction(function () use ($mismatches) {
            foreach ($mismatches as [$item, $realPaid]) {
                $newBalance = max(0, (float) $item->amount - $realPaid);
                $item->update([
                    'amount_paid' => $realPaid,
                    'balance' => $newBalance,
                    'status' => $newBalance <= 0 ? 'paid' : ($realPaid > 0 ? 'partial' : 'pending'),
                ]);
            }
        });

        $this->info("Recomputed {$mismatches->count()} fee item(s).");

        return self::SUCCESS;
    }
}
