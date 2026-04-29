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
        DB::transaction(function () use ($payment) {

            $remaining = (float) $payment->amount;

            if ($remaining <= 0) {
                return;
            }

            // 🔴 Lock fee items to prevent race conditions
            $feeItemsQuery = StudentFeeItem::query()
                ->where('student_id', $payment->student_id)
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('charge_date')
                ->orderBy('id')
                ->lockForUpdate();

            // ✅ Respect enrollment scope
            if (!empty($payment->enrollment_id)) {
                $feeItemsQuery->where(function ($q) use ($payment) {
                    $q->where('enrollment_id', $payment->enrollment_id)
                        ->orWhereNull('enrollment_id'); // include student-once
                });
            }

            $feeItems = $feeItemsQuery->get();

            foreach ($feeItems as $item) {

                if ($remaining <= 0) {
                    break;
                }

                $itemBalance = (float) $item->balance;

                if ($itemBalance <= 0) {
                    continue;
                }

                $allocatable = min($remaining, $itemBalance);

                // ✅ Create allocation
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'student_fee_item_id' => $item->id,
                    'amount_allocated' => $allocatable,
                ]);

                // ✅ Update fee item
                $newAmountPaid = (float) $item->amount_paid + $allocatable;
                $newBalance = (float) $item->amount - $newAmountPaid;

                // Prevent float issues
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

                $remaining -= $allocatable;
            }

            // 🟡 Optional: handle overpayment (important for real systems)
            if ($remaining > 0) {
                // You can:
                // 1. leave as unallocated
                // 2. store in payment.unallocated_amount
                
                // 3. log it

                \Log::warning('Unallocated payment amount detected', [
                    'payment_id' => $payment->id,
                    'student_id' => $payment->student_id,
                    'remaining_amount' => $remaining,
                ]);
            }
        });
    }
}
