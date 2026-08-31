<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Payment;
use App\Models\StudentFeeItem;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * Handles all credit-side adjustments to a student's financial ledger:
 * discounts, scholarships, waivers, and overpayment credits.
 *
 * Strategy:
 * - Discounts & scholarships are posted as a Payment with method='credit',
 *   so the normal allocation engine clears them against outstanding fees.
 *   This makes them visible in the payment history with proper attribution.
 *
 * - Waivers mark the specific fee item as waived, zeroing its balance without
 *   creating a payment record. They are only visible in the statement.
 */
class CreditService
{
    public function __construct(
        private PaymentPostingService $paymentService,
        private JournalPostingService $journalPostingService
    ) {}

    /**
     * Apply a fixed-amount discount to an enrollment.
     * The credit is posted as a payment and allocated against outstanding fees.
     */
    public function applyDiscount(
        Enrollment $enrollment,
        float $amount,
        string $reason,
        User $admin
    ): Payment {
        if ($amount <= 0) {
            throw new LogicException('Discount amount must be positive.');
        }

        return $this->postCredit(
            enrollment: $enrollment,
            amount: $amount,
            creditType: 'discount',
            reason: $reason,
            appliedBy: $admin
        );
    }

    /**
     * Apply a scholarship credit to an enrollment.
     */
    public function applyScholarship(
        Enrollment $enrollment,
        float $amount,
        string $reason,
        User $admin
    ): Payment {
        if ($amount <= 0) {
            throw new LogicException('Scholarship amount must be positive.');
        }

        return $this->postCredit(
            enrollment: $enrollment,
            amount: $amount,
            creditType: 'scholarship',
            reason: $reason,
            appliedBy: $admin
        );
    }

    /**
     * Apply a credit transfer (e.g. from an external institution or a previous course).
     */
    public function applyCreditTransfer(
        Enrollment $enrollment,
        float $amount,
        string $reason,
        User $admin
    ): Payment {
        if ($amount <= 0) {
            throw new LogicException('Credit transfer amount must be positive.');
        }

        return $this->postCredit(
            enrollment: $enrollment,
            amount: $amount,
            creditType: 'credit_transfer',
            reason: $reason,
            appliedBy: $admin
        );
    }

    /**
     * Apply an overpayment as a credit for a future charge.
     * Used when a student's unallocated payment balance is converted to an explicit credit.
     */
    public function applyOverpaymentCredit(
        Enrollment $enrollment,
        float $amount,
        User $admin
    ): Payment {
        return $this->postCredit(
            enrollment: $enrollment,
            amount: $amount,
            creditType: 'overpayment_credit',
            reason: 'Overpayment credit applied from unallocated balance.',
            appliedBy: $admin
        );
    }

    /**
     * Waive a specific fee item entirely or partially.
     * Waived items are NOT treated as paid — they simply no longer contribute to the balance.
     * No payment record is created; the waiver appears only in the statement.
     */
    public function waiveFee(
        StudentFeeItem $feeItem,
        string $reason,
        User $admin,
        float $waiveAmount = null
    ): void {
        if (in_array($feeItem->status, ['paid', 'waived', 'cancelled'], true)) {
            throw new LogicException(
                "Fee item #{$feeItem->id} cannot be waived — it is already '{$feeItem->status}'."
            );
        }

        $waiveAmount = $waiveAmount ?? (float) $feeItem->balance;

        if ($waiveAmount <= 0 || $waiveAmount > (float) $feeItem->balance) {
            throw new LogicException('Waive amount must be > 0 and ≤ remaining balance.');
        }

        DB::transaction(function () use ($feeItem, $waiveAmount, $reason, $admin) {
            $oldBalance = (float) $feeItem->balance;
            $newBalance = max(0, $oldBalance - $waiveAmount);

            // Record the waiver as a note on the fee item itself
            $feeItem->update([
                'balance'      => $newBalance,
                'credit_type'  => 'waiver',
                'applied_by'   => $admin->id,
                'credit_reason' => $reason,
                'status'       => $newBalance <= 0 ? 'waived' : 'partial',
            ]);

            \App\Models\FeeItemAudit::create([
                'student_fee_item_id' => $feeItem->id,
                'user_id' => $admin->id,
                'action' => 'waived',
                'old_values' => ['balance' => $oldBalance],
                'new_values' => ['balance' => $newBalance],
                'reason' => $reason,
            ]);
        });

        // waiveFee() bypasses Payment entirely (unlike discounts/scholarships,
        // which route through postCredit() -> PaymentPostingService), so it
        // needs its own explicit GL posting call.
        $this->postWaiverToLedger($feeItem, $waiveAmount, $reason, $admin);
    }

    protected function postWaiverToLedger(StudentFeeItem $feeItem, float $waiveAmount, string $reason, User $admin): void
    {
        try {
            $this->journalPostingService->post([
                'entry_date'  => now()->toDateString(),
                'description' => "Fee waiver — {$feeItem->description} ({$reason})",
                'source_type' => StudentFeeItem::class,
                'source_id'   => $feeItem->id,
                'created_by'  => $admin->id,
                'lines' => [
                    ['account_code' => config('accounting.waiver_expense_account_code'), 'debit' => $waiveAmount],
                    ['account_code' => config('accounting.student_debtors_account_code'), 'credit' => $waiveAmount],
                ],
            ]);
        } catch (LogicException $e) {
            Log::warning("GL posting failed for waived StudentFeeItem #{$feeItem->id}: {$e->getMessage()}");
        }
    }

    /**
     * Post a credit as a payment record so it flows through the allocation engine.
     */
    private function postCredit(
        Enrollment $enrollment,
        float $amount,
        string $creditType,
        string $reason,
        User $appliedBy
    ): Payment {
        return $this->paymentService->post([
            'student_id'    => $enrollment->student_id,
            'enrollment_id' => $enrollment->id,
            'payment_date'  => now()->toDateString(),
            'amount'        => $amount,
            'method'        => 'credit',
            'reference'     => strtoupper($creditType) . '-' . now()->format('YmdHis'),
            'status'        => 'completed',
            'notes'         => "[{$creditType}] {$reason}",
            'payer'         => $appliedBy->name . ' (Admin)',
        ]);
    }
}
