<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentMethodAccountMap;
use App\Models\StudentFeeItem;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

class PaymentPostingService
{
    public function __construct(
        private JournalPostingService $journalPostingService
    ) {}

    public function post(array $data): Payment
    {
        $payment = DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'student_id'          => $data['student_id'],
                'enrollment_id'       => $data['enrollment_id'] ?? null,
                'payment_date'        => $data['payment_date'],
                'amount'              => $data['amount'],
                'unallocated_balance' => $data['amount'],
                'method'              => $data['method'] ?? null,
                'reference'           => $data['reference'] ?? null,
                'receipt_no'          => $data['receipt_no'] ?? null,
                'status'              => $data['status'] ?? 'completed',
                'notes'               => $data['notes'] ?? null,
                'paid_at'             => now(),
                'payer'               => $data['payer'] ?? null,
                'phone'               => $data['phone'] ?? null,
                'is_sponsored'        => $data['is_sponsored'] ?? false,
                'sponsored_by'        => $data['sponsored_by'] ?? null,
                'sponsor_reference'   => $data['sponsor_reference'] ?? null,
            ]);

            $this->allocatePayment($payment);

            return $payment->fresh()->load(['allocations.studentFeeItem']);
        });

        // GL posting is intentionally outside the allocation transaction's
        // failure path: money has already changed hands, so a GL error must
        // never roll back or block the payment itself — see resolveMethodAccountCode().
        $this->postPaymentToLedger($payment, $data['created_by'] ?? null);

        return $payment;
    }

    protected function postPaymentToLedger(Payment $payment, ?int $createdBy): void
    {
        try {
            $this->journalPostingService->post([
                'entry_date'  => $payment->payment_date,
                'description' => "Payment received — {$payment->reference}",
                'reference'   => $payment->receipt_no ?? $payment->reference,
                'source_type' => Payment::class,
                'source_id'   => $payment->id,
                'created_by'  => $createdBy,
                'lines' => [
                    ['account_code' => $this->resolveMethodAccountCode($payment->method), 'debit' => $payment->amount],
                    ['account_code' => config('accounting.student_debtors_account_code'), 'credit' => $payment->amount],
                ],
            ]);
        } catch (LogicException $e) {
            Log::warning("GL posting failed for Payment #{$payment->id}: {$e->getMessage()}");
        }
    }

    protected function resolveMethodAccountCode(?string $method): string
    {
        $map = $method
            ? PaymentMethodAccountMap::active()->where('method', $method)->first()
            : null;

        if ($map) {
            return $map->account->account_code;
        }

        if ($method) {
            Log::warning("Payment method '{$method}' has no chart-of-accounts mapping; posting to default cash account.");
        }

        return config('accounting.default_cash_account_code');
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

            ->orderBy('fee_definitions.priority_order')

            ->orderByRaw("
            CASE
                WHEN student_fee_items.enrollment_id = ?
                THEN 0
                ELSE 1
            END
        ", [$payment->enrollment_id])

            ->orderBy('student_fee_items.charge_date')

            ->orderBy('student_fee_items.id')

            ->lockForUpdate()

            ->get();
    }
}
