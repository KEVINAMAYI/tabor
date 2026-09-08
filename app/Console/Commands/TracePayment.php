<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Finance\StudentLedgerService;
use Illuminate\Console\Command;

/**
 * Read-only diagnostic: given a payment reference/transaction_id/receipt_no,
 * dumps the real Payment row, every PaymentAllocation with its target fee
 * item and progression, and renders the actual statement for every
 * progression touched — so a "why does this statement show X" question can
 * be answered against real data instead of guessed at.
 */
class TracePayment extends Command
{
    protected $signature = 'finance:trace-payment {reference : Payment reference, transaction_id, or receipt_no}';

    protected $description = 'Read-only trace of a payment: its allocations and every statement they appear on';

    public function handle(StudentLedgerService $ledgerService): int
    {
        $reference = $this->argument('reference');

        $payments = Payment::query()
            ->where('reference', $reference)
            ->orWhere('transaction_id', $reference)
            ->orWhere('receipt_no', $reference)
            ->with(['allocations.studentFeeItem.enrollment.course', 'allocations.studentFeeItem.progression.trimester'])
            ->get();

        if ($payments->isEmpty()) {
            $this->error("No payment found matching reference/transaction_id/receipt_no '{$reference}'.");
            return self::FAILURE;
        }

        foreach ($payments as $payment) {
            $this->components->info("Payment #{$payment->id}");
            $this->table(['Field', 'Value'], [
                ['amount', number_format((float) $payment->amount, 2)],
                ['unallocated_balance', number_format((float) $payment->unallocated_balance, 2)],
                ['student_id', $payment->student_id ?? 'NULL'],
                ['enrollment_id', $payment->enrollment_id ?? 'NULL'],
                ['payment_date', $payment->payment_date],
                ['method', $payment->method],
                ['reference', $payment->reference ?: '(blank)'],
                ['transaction_id', $payment->transaction_id ?: '(blank)'],
                ['receipt_no', $payment->receipt_no ?: '(blank)'],
            ]);

            if ($payment->allocations->isEmpty()) {
                $this->warn('No allocations at all — fully unallocated.');
                $this->newLine();
                continue;
            }

            $allocRows = $payment->allocations->map(function ($a) {
                $item = $a->studentFeeItem;
                return [
                    $a->id,
                    number_format((float) $a->amount_allocated, 2),
                    $item?->id,
                    $item?->description,
                    $item?->enrollment?->course?->code,
                    $item?->enrollment_progression_id,
                    $item?->progression?->trimester?->name,
                    number_format((float) ($item?->balance ?? 0), 2),
                ];
            });
            $this->table(['Alloc #', 'Allocated', 'Fee Item #', 'Description', 'Course', 'Progression #', 'Trimester', 'Item balance now'], $allocRows);

            $progressions = $payment->allocations->pluck('studentFeeItem.progression')->filter()->unique('id');

            foreach ($progressions as $progression) {
                $student = $progression->student ?? $payment->allocations->first(
                    fn ($a) => (int) $a->studentFeeItem?->enrollment_progression_id === (int) $progression->id
                )?->studentFeeItem?->student;

                if (!$student) {
                    continue;
                }

                $stmt = $ledgerService->buildProgressionStatement($student, $progression);
                $paymentRow = $stmt['ledger']->first(fn ($e) => ($e['source_type'] ?? null) === 'payment' && (int) ($e['payment_id'] ?? 0) === (int) $payment->id);

                $this->line(sprintf(
                    "  -> Progression #%d (%s, %s): opening=%.2f closing=%.2f | this payment shows here as: %s",
                    $progression->id,
                    $progression->enrollment?->course?->code,
                    $progression->status,
                    $stmt['opening_balance'],
                    $stmt['closing_balance'],
                    $paymentRow ? number_format((float) $paymentRow['cr'], 2) : 'NOT SHOWN'
                ));
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
