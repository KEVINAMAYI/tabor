<?php

namespace App\Console\Commands;

use App\Models\FeeItemAudit;
use App\Models\Payment;
use App\Models\StudentFeeItem;
use App\Services\PaymentPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finds StudentFeeItem rows with no enrollment_progression_id at all —
 * orphaned duplicates that can never appear on any statement, yet may still
 * hold real payment allocations (money collected against a charge nobody
 * can see). Confirmed case: StudentFeeItem #399 for Kelvin Nderitu, a
 * duplicate "Attachment Fee" alongside the correctly-linked #844, holding
 * 5,000 in real payments.
 *
 * --fix reverses each orphan's PaymentAllocation rows (restoring the
 * unallocated balance on the originating payments), voids the orphaned
 * item (status=cancelled, amount/balance zeroed), then re-runs
 * PaymentPostingService::allocateExistingPayment() on each affected
 * payment so the freed-up money re-sweeps onto the student's real
 * outstanding balance instead of vanishing.
 */
class VoidOrphanedDuplicateFeeItems extends Command
{
    protected $signature = 'finance:void-orphaned-duplicate-fee-items {--fix : Actually void and re-sweep. Without this flag, only previews.}';

    protected $description = 'Void StudentFeeItems with no enrollment_progression_id and re-sweep their payments (dry-run by default)';

    public function handle(PaymentPostingService $paymentPostingService): int
    {
        $orphans = StudentFeeItem::query()
            ->whereNull('enrollment_progression_id')
            ->with(['student', 'allocations.payment'])
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No orphaned (progression-less) fee items found.');
            return self::SUCCESS;
        }

        $rows = $orphans->map(fn (StudentFeeItem $item) => [
            $item->id,
            trim(($item->student?->first_name ?? '') . ' ' . ($item->student?->last_name ?? '')),
            $item->description,
            $item->charge_date,
            number_format((float) $item->amount, 2),
            number_format((float) $item->allocations->sum('amount_allocated'), 2),
        ]);

        $this->table(['Item #', 'Student', 'Description', 'Charge Date', 'Amount', 'Allocated'], $rows);

        if (!$this->option('fix')) {
            $this->warn('DRY RUN — nothing was changed. Re-run with --fix to void these and re-sweep their payments.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Void {$orphans->count()} orphaned fee item(s) and re-sweep their payments?", false)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        foreach ($orphans as $item) {
            $affectedPayments = $item->allocations->pluck('payment')->filter()->unique('id');

            DB::transaction(function () use ($item) {
                $oldValues = [
                    'amount' => (float) $item->amount,
                    'amount_paid' => (float) $item->amount_paid,
                    'balance' => (float) $item->balance,
                    'status' => $item->status,
                ];

                foreach ($item->allocations as $allocation) {
                    $payment = $allocation->payment;
                    $allocation->delete();

                    if ($payment) {
                        $payment->update([
                            'unallocated_balance' => (float) $payment->unallocated_balance + (float) $allocation->amount_allocated,
                        ]);
                    }
                }

                $item->update([
                    'amount' => 0,
                    'amount_paid' => 0,
                    'balance' => 0,
                    'status' => 'cancelled',
                ]);

                FeeItemAudit::create([
                    'student_fee_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'action' => 'voided_orphaned_duplicate',
                    'old_values' => $oldValues,
                    'new_values' => ['amount' => 0, 'amount_paid' => 0, 'balance' => 0, 'status' => 'cancelled'],
                    'reason' => 'Orphaned duplicate fee item (no enrollment_progression_id) voided via finance:void-orphaned-duplicate-fee-items; its collected payments were re-swept onto the student\'s real outstanding balance.',
                ]);
            });

            foreach ($affectedPayments as $payment) {
                $paymentPostingService->allocateExistingPayment($payment->fresh());
            }

            $this->info("Voided item #{$item->id} and re-swept " . $affectedPayments->count() . ' payment(s).');
        }

        return self::SUCCESS;
    }
}
