<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\StudentFeeItem;
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
                'unallocated_balance' => $data['amount'],
                'method' => $data['method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'receipt_no' => $data['receipt_no'] ?? null,
                'status' => $data['status'] ?? 'completed',
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
                'payer' => $data['payer'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            $this->allocatePayment($payment);

            return $payment->fresh()->load(['allocations.studentFeeItem']);
        });
    }

    public function allocateExistingPayment(Payment $payment): void
    {

        DB::transaction(function () use ($payment) {
            $this->allocatePayment($payment->fresh());
        });
    }

    protected function allocatePayment(Payment $payment): void
    {
        $alreadyAllocated = (float) $payment->allocations()->sum('amount_allocated');

        $remaining = max(0, (float) $payment->amount - $alreadyAllocated);

        if ($remaining <= 0) {
            $payment->update([
                'unallocated_balance' => 0,
            ]);

            return;
        }

        $feeItems = $this->outstandingFeeItemsForPayment($payment);

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

        $payment->update([
            'unallocated_balance' => max(0, $remaining),
        ]);
    }

    protected function outstandingFeeItemsForPayment(Payment $payment)
    {
        $studentId = $payment->student_id;

        if (
            empty($studentId) &&
            !empty($payment->enrollment_id)
        ) {
            $studentId = optional(
                $payment->enrollment
            )->student_id;
        }

        if (empty($studentId)) {
            return collect();
        }

        return StudentFeeItem::query()
            ->select('student_fee_items.*')

            ->leftJoin(
                'fee_definitions',
                'fee_definitions.id',
                '=',
                'student_fee_items.fee_definition_id'
            )

            ->where('student_fee_items.student_id', $studentId)

            ->where('student_fee_items.balance', '>', 0)

            ->whereIn('student_fee_items.status', [
                'pending',
                'partial',
            ])

            ->when(!empty($payment->enrollment_id), function ($query) use ($payment) {
                $query->where(function ($q) use ($payment) {
                    $q->where('student_fee_items.enrollment_id', $payment->enrollment_id)
                        ->orWhereNull('student_fee_items.enrollment_id');
                });
            })

            /*
            |--------------------------------------------------------------------------
            | Priority Allocation Order
            |--------------------------------------------------------------------------
            |
            | 1. Student-once fees first
            | 2. Same enrollment as payment first
            | 3. Other enrollments for same student after
            | 4. Oldest charges
            |
            */

            ->orderByRaw("
    CASE
        WHEN fee_definitions.scope = 'student'
         AND fee_definitions.applies_once = 1
        THEN 0

        WHEN LOWER(COALESCE(fee_definitions.name, '')) LIKE '%tuition%'
          OR LOWER(COALESCE(student_fee_items.description, '')) LIKE '%tuition%'
        THEN 1

        ELSE 2
    END ASC
")
            ->orderBy('student_fee_items.charge_date', 'asc')
            ->orderBy('student_fee_items.id', 'asc')

            ->lockForUpdate()

            ->get();
    }
}
