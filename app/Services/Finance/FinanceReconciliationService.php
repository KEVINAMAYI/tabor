<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\StudentFeeItem;
use Illuminate\Support\Collection;

/**
 * Single source of truth for detecting drift between StudentFeeItem/Payment
 * records and their PaymentAllocation sums. Used by both the
 * `finance:reconcile` console command and the on-screen Reconciliation
 * Health report — extracted here so the two never diverge.
 */
class FinanceReconciliationService
{
    public function detectMismatches(int $studentId): Collection
    {
        $mismatches = collect();

        // --- Fee items ---
        $feeItems = StudentFeeItem::query()
            ->where('student_id', $studentId)
            ->withSum('allocations', 'amount_allocated')
            ->get();

        foreach ($feeItems as $item) {
            $amount       = (float) $item->amount;
            $sumAllocated = (float) ($item->allocations_sum_amount_allocated ?? 0);
            $recorded     = (float) $item->amount_paid;
            $isCredit     = $amount < 0; // discount / scholarship / credit items

            // Skip credit/negative-amount items — their balance is legitimately negative
            // and allocations are always zero (they're not settled via payment allocations).
            if ($isCredit) {
                continue;
            }

            // 1a. Over-allocation (allocations exceed the fee amount — unusual, manual resolution needed)
            if ($sumAllocated > $amount + 0.01) {
                $mismatches->push([
                    'type'     => 'FeeItem.over_allocated',
                    'id'       => $item->id,
                    'expected' => $amount,
                    'actual'   => $sumAllocated,
                    'diff'     => round($sumAllocated - $amount, 2),
                ]);
            }

            // 1b. amount_paid drift (cap expected at fee amount to handle over-allocation correctly)
            $expectedPaid = min($sumAllocated, $amount);
            if (abs($expectedPaid - $recorded) > 0.01) {
                $mismatches->push([
                    'type'     => 'FeeItem.amount_paid',
                    'id'       => $item->id,
                    'expected' => $expectedPaid,
                    'actual'   => $recorded,
                    'diff'     => round($expectedPaid - $recorded, 2),
                ]);
            }

            // 2. balance drift  (max(0,…) is correct for charges; credits are skipped above)
            $expectedBalance = max(0, $amount - $sumAllocated);
            if (abs($expectedBalance - (float) $item->balance) > 0.01) {
                $mismatches->push([
                    'type'     => 'FeeItem.balance',
                    'id'       => $item->id,
                    'expected' => $expectedBalance,
                    'actual'   => (float) $item->balance,
                    'diff'     => round($expectedBalance - (float) $item->balance, 2),
                ]);
            }

            // 3. status inconsistency
            $expectedStatus = match (true) {
                $item->status === 'waived'              => 'waived',
                $sumAllocated <= 0                      => 'pending',
                $sumAllocated >= $amount - 0.01         => 'paid',
                default                                 => 'partial',
            };

            if ($item->status !== $expectedStatus && $item->status !== 'waived') {
                $mismatches->push([
                    'type'     => 'FeeItem.status',
                    'id'       => $item->id,
                    'expected' => $expectedStatus,
                    'actual'   => $item->status,
                    'diff'     => 0,
                ]);
            }
        }

        // --- Payments ---
        $payments = Payment::query()
            ->where('student_id', $studentId)
            ->withSum('allocations', 'amount_allocated')
            ->get();

        foreach ($payments as $payment) {
            $sumAllocated        = (float) ($payment->allocations_sum_amount_allocated ?? 0);
            $expectedUnallocated = max(0, (float) $payment->amount - $sumAllocated);
            $recordedUnallocated = (float) $payment->unallocated_balance;

            // 4. unallocated_balance drift
            if (abs($expectedUnallocated - $recordedUnallocated) > 0.01) {
                $mismatches->push([
                    'type'     => 'Payment.unallocated_balance',
                    'id'       => $payment->id,
                    'expected' => $expectedUnallocated,
                    'actual'   => $recordedUnallocated,
                    'diff'     => round($expectedUnallocated - $recordedUnallocated, 2),
                ]);
            }

            // 5. Per-enrollment: unallocated payment with outstanding fees on the SAME enrollment
            // (cross-enrollment unallocated is intentional — admin must allocate manually)
            if ($expectedUnallocated > 0.01 && !empty($payment->enrollment_id)) {
                $hasMatchingOutstanding = StudentFeeItem::query()
                    ->where('student_id', $studentId)
                    ->where('enrollment_id', $payment->enrollment_id)
                    ->where('balance', '>', 0.01)
                    ->whereIn('status', ['pending', 'partial'])
                    ->exists();

                if ($hasMatchingOutstanding) {
                    $mismatches->push([
                        'type'     => 'Payment.money_not_applied',
                        'id'       => $payment->id,
                        'expected' => 0,
                        'actual'   => round($expectedUnallocated, 2),
                        'diff'     => round(-$expectedUnallocated, 2),
                    ]);
                }
            }
        }

        return $mismatches;
    }
}
