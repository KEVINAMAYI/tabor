<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

/**
 * Backfills Payment.student_id/enrollment_id for payments where both are
 * null despite having a real, correct PaymentAllocation — caused by the
 * addPayment()/updatePayment() bug (fixed alongside this command) where
 * saving via the modal without filling the top "Default Student /
 * Enrollment" search field wiped student_id/enrollment_id to null, even
 * when a student was correctly picked directly in an allocation row.
 *
 * Purely cosmetic/data-hygiene: StudentLedgerService's statement
 * calculation and FinanceReconciliationService both already key off the
 * PaymentAllocation -> StudentFeeItem chain, not Payment.student_id, so
 * statements were already correct. This just fixes the Payments list's
 * "Student" column showing "Multiple / Unmapped" for these rows.
 */
class BackfillPaymentStudentLinks extends Command
{
    protected $signature = 'finance:backfill-payment-student-links
        {--fix : Actually backfill. Without this flag, only previews.}';

    protected $description = 'Backfill Payment.student_id/enrollment_id from allocations for payments where both are null (dry-run by default)';

    public function handle(): int
    {
        $payments = Payment::query()
            ->whereNull('student_id')
            ->whereNull('enrollment_id')
            ->whereHas('allocations.studentFeeItem')
            ->with('allocations.studentFeeItem.student')
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No payments need backfilling.');
            return self::SUCCESS;
        }

        $rows = $payments->map(function (Payment $payment) {
            $item = $payment->allocations->first()?->studentFeeItem;

            $studentLabel = $item?->student
                ? trim("{$item->student->first_name} {$item->student->last_name}")
                : "student #{$item?->student_id}";

            return [
                $payment->id,
                number_format($payment->amount, 2),
                $studentLabel,
                $item?->student_id,
                $item?->enrollment_id,
            ];
        });

        $this->table(['Payment #', 'Amount', 'Student', 'student_id', 'enrollment_id'], $rows);

        if (!$this->option('fix')) {
            $this->warn('DRY RUN — nothing was changed. Re-run with --fix to apply.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Backfill {$payments->count()} payment(s)?", false)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        foreach ($payments as $payment) {
            $item = $payment->allocations->first()?->studentFeeItem;

            if (!$item) {
                continue;
            }

            $payment->update([
                'student_id' => $item->student_id,
                'enrollment_id' => $item->enrollment_id,
            ]);
        }

        $this->info("Backfilled {$payments->count()} payment(s).");

        return self::SUCCESS;
    }
}
