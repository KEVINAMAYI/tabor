<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\StudentFeeItem;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class PaymentPostingService
{
    public function post(array $data): Payment
    {

        return DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'student_id' => $data['student_id'],
                'enrollment_id' => $data['enrollment_id'] ?? null,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'method' => $data['method'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'receipt_no' => $data['receipt_no'] ?? null,
                'status' => $data['status'] ?? 'completed',
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
                'payer' => $data['payer'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            $remainingAmount = (float) $payment->amount;

            $feeItemsQuery = StudentFeeItem::query()
                ->where('student_id', $payment->student_id)
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('charge_date')
                ->orderBy('id');

            if (!empty($payment->enrollment_id)) {
                $feeItemsQuery->where(function ($query) use ($payment) {
                    $query->where('enrollment_id', $payment->enrollment_id)
                        ->orWhereNull('enrollment_id');
                });
            }

            $feeItems = $feeItemsQuery->lockForUpdate()->get();

            foreach ($feeItems as $item) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $itemBalance = (float) $item->balance;

                if ($itemBalance <= 0) {
                    continue;
                }

                $allocatable = min($remainingAmount, $itemBalance);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'student_fee_item_id' => $item->id,
                    'amount_allocated' => $allocatable,
                ]);

                $newAmountPaid = (float) $item->amount_paid + $allocatable;
                $newBalance = (float) $item->amount - $newAmountPaid;

                if ($newBalance < 0) {
                    $newBalance = 0;
                }

                $item->update([
                    'amount_paid' => $newAmountPaid,
                    'balance' => $newBalance,
                    'status' => $newBalance == 0.0
                        ? 'paid'
                        : ($newAmountPaid > 0 ? 'partial' : 'pending'),
                ]);

                $remainingAmount -= $allocatable;
            }

            return $payment->load(['allocations.studentFeeItem']);
        });
    }


    public function allocateExistingPayment(Payment $payment): void
    {
        if ($payment->allocations()->exists()) {
            return;
        }

        $remaining = (float) $payment->amount;

        if ($remaining <= 0) {
            return;
        }

        $feeItemsQuery = StudentFeeItem::query()
            ->where('student_id', $payment->student_id)
            ->whereIn('status', ['pending', 'partial']);

        if (!empty($payment->enrollment_id)) {
            $feeItemsQuery->where(function ($q) use ($payment) {
                $q->whereNull('enrollment_id') // student-once fees
                    ->orWhere('enrollment_id', $payment->enrollment_id);
            });
        }

        $feeItems = $feeItemsQuery
            ->orderByRaw("
            CASE
                WHEN enrollment_id IS NULL THEN 0
                ELSE 1
            END
        ")
            ->orderBy('charge_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($feeItems as $item) {
            if ($remaining <= 0) {
                break;
            }

            $itemBalance = (float) $item->balance;

            if ($itemBalance <= 0) {
                continue;
            }

            $allocatable = min($remaining, $itemBalance);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'student_fee_item_id' => $item->id,
                'amount_allocated' => $allocatable,
            ]);

            $newAmountPaid = (float) $item->amount_paid + $allocatable;
            $newBalance = max(0, (float) $item->amount - $newAmountPaid);

            $item->update([
                'amount_paid' => $newAmountPaid,
                'balance' => $newBalance,
                'status' => $newBalance <= 0
                    ? 'paid'
                    : ($newAmountPaid > 0 ? 'partial' : 'pending'),
            ]);

            $remaining -= $allocatable;
        }

        if ($remaining > 0) {
            \Log::warning('Payment has unallocated amount', [
                'payment_id' => $payment->id,
                'student_id' => $payment->student_id,
                'remaining_amount' => $remaining,
            ]);
        }
    }
}
