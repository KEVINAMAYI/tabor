<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentRefund;
use App\Models\StudentFeeItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class RefundService
{
    /**
     * Create a pending refund request against a payment.
     */
    public function initiateRefund(
        Payment $payment,
        float $amount,
        string $reason
    ): PaymentRefund {
        if ($amount <= 0) {
            throw new LogicException('Refund amount must be greater than zero.');
        }

        $maxRefundable = (float) $payment->amount - $payment->totalRefunded();

        if ($amount > $maxRefundable) {
            throw new LogicException(
                "Refund amount ({$amount}) exceeds maximum refundable ({$maxRefundable}) for payment #{$payment->id}."
            );
        }

        if ($payment->refunds()->where('status', 'pending')->exists()) {
            throw new LogicException("Payment #{$payment->id} already has a pending refund.");
        }

        return PaymentRefund::create([
            'payment_id' => $payment->id,
            'amount'     => $amount,
            'reason'     => $reason,
            'status'     => 'pending',
        ]);
    }

    /**
     * Process (execute) a pending refund.
     * - Reverses allocations for the refund amount (newest first to minimise disruption)
     * - Restores StudentFeeItem balances
     * - Updates the payment's unallocated_balance
     * - Marks the refund as processed
     */
    public function processRefund(
        PaymentRefund $refund,
        string $method,
        string $reference = null,
        ?User $admin = null
    ): void {
        if (!$refund->isPending()) {
            throw new LogicException("Refund #{$refund->id} is not pending.");
        }

        $payment = $refund->payment;

        DB::transaction(function () use ($refund, $payment, $method, $reference, $admin) {
            $remaining = (float) $refund->amount;

            // Reverse allocations newest-first so we unwind the most recent charges
            $allocations = PaymentAllocation::query()
                ->where('payment_id', $payment->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                if ($remaining <= 0) {
                    break;
                }

                $reversal = min($remaining, (float) $allocation->amount_allocated);

                if ($reversal <= 0) {
                    continue;
                }

                $allocation->decrement('amount_allocated', $reversal);

                if ((float) $allocation->amount_allocated <= 0) {
                    $allocation->delete();
                }

                // Restore the fee item balance
                $feeItem = StudentFeeItem::find($allocation->student_fee_item_id);
                if ($feeItem) {
                    $newAmountPaid = max(0, (float) $feeItem->amount_paid - $reversal);
                    $newBalance    = (float) $feeItem->amount - $newAmountPaid;

                    $feeItem->update([
                        'amount_paid' => $newAmountPaid,
                        'balance'     => max(0, $newBalance),
                        'status'      => $newAmountPaid <= 0
                            ? 'pending'
                            : ($newBalance > 0 ? 'partial' : 'paid'),
                    ]);
                }

                $remaining -= $reversal;
            }

            // Update payment unallocated balance
            $payment->increment('unallocated_balance', $refund->amount);

            $refund->update([
                'status'       => 'processed',
                'method'       => $method,
                'reference'    => $reference,
                'processed_at' => now(),
                'processed_by' => $admin?->id,
            ]);
        });
    }

    /**
     * Cancel a pending refund request.
     */
    public function cancelRefund(PaymentRefund $refund): void
    {
        if (!$refund->isPending()) {
            throw new LogicException("Only pending refunds can be cancelled.");
        }

        $refund->update(['status' => 'cancelled']);
    }
}
