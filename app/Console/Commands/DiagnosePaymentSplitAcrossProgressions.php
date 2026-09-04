<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\StudentFeeItem;
use Illuminate\Console\Command;

/**
 * Read-only production/staging audit for the "statements not balancing"
 * incident (2026-09). Three checks, none of them write anything:
 *
 * 1. Orphaned fee items — StudentFeeItem rows with no
 *    enrollment_progression_id that still hold real payment allocations.
 *    These can never appear on any statement; the confirmed case is a
 *    duplicate charge (see finance:void-orphaned-duplicate-fee-items).
 *
 * 2. Split-payment integrity — every payment whose allocations span more
 *    than one trimester/progression. Reports whether the allocations sum
 *    to the payment's own amount (expected: yes, always — a FIFO overflow
 *    from one trimester into the next, correctly visible on both
 *    statements) or something else (would indicate real corruption, not
 *    yet seen in dev).
 *
 * 3. Cross-enrollment splits — payments whose allocations land on
 *    different enrollments (different courses) entirely, not just
 *    different trimesters of the same course. These are created via the
 *    admin payment modal's per-row allocation and may be intentional
 *    (one lump sum covering two concurrent courses) — flagged for human
 *    review, not auto-corrected.
 */
class DiagnosePaymentSplitAcrossProgressions extends Command
{
    protected $signature = 'finance:diagnose-split-payments
        {--student= : Admission number or student id to check. Omit to scan all students.}';

    protected $description = 'Read-only audit: orphaned fee items, split-payment integrity, cross-enrollment allocations';

    public function handle(): int
    {
        $studentOption = $this->option('student');

        $this->auditOrphanedFeeItems($studentOption);
        $this->newLine();
        $this->auditSplitPayments($studentOption);

        return self::SUCCESS;
    }

    protected function auditOrphanedFeeItems(?string $studentOption): void
    {
        $this->components->info('1. Orphaned fee items (no enrollment_progression_id) with real payments');

        $orphans = StudentFeeItem::query()
            ->whereNull('enrollment_progression_id')
            ->whereHas('allocations')
            ->with('student')
            ->when($studentOption, function ($q) use ($studentOption) {
                $q->whereHas('student', function ($sq) use ($studentOption) {
                    $sq->where('admission_number', $studentOption)->orWhere('id', $studentOption);
                });
            })
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('None found.');
            return;
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
        $this->warn("{$orphans->count()} orphaned item(s) found — run finance:void-orphaned-duplicate-fee-items to preview a fix.");
    }

    protected function auditSplitPayments(?string $studentOption): void
    {
        $this->components->info('2 & 3. Split-payment integrity and cross-enrollment allocations');

        $payments = Payment::query()
            ->whereHas('allocations.studentFeeItem')
            ->with(['allocations.studentFeeItem.enrollment.course', 'allocations.studentFeeItem.student'])
            ->when($studentOption, function ($q) use ($studentOption) {
                $q->whereHas('allocations.studentFeeItem.student', function ($sq) use ($studentOption) {
                    $sq->where('admission_number', $studentOption)->orWhere('id', $studentOption);
                });
            })
            ->get()
            ->filter(fn (Payment $p) => $p->allocations->pluck('studentFeeItem.enrollment_progression_id')->unique()->count() > 1);

        if ($payments->isEmpty()) {
            $this->info('No payments span multiple progressions.');
            return;
        }

        $mismatches = collect();
        $crossEnrollment = collect();
        $okCount = 0;

        foreach ($payments as $payment) {
            $totalAllocated = round((float) $payment->allocations->sum('amount_allocated'), 2);
            $paymentAmount = round((float) $payment->amount, 2);
            $isMismatch = abs($totalAllocated - $paymentAmount) >= 0.01;
            $spansEnrollments = $payment->allocations->pluck('studentFeeItem.enrollment_id')->unique()->count() > 1;

            if ($isMismatch) {
                $mismatches->push([$payment, $totalAllocated, $paymentAmount]);
            } elseif ($spansEnrollments) {
                $crossEnrollment->push($payment);
            } else {
                $okCount++;
            }
        }

        $this->info("{$okCount} payment(s) split across trimesters of the SAME course — allocations balance exactly, expected FIFO overflow, no action needed.");

        if ($mismatches->isNotEmpty()) {
            $this->error("{$mismatches->count()} payment(s) with a REAL mismatch — allocations do not sum to the payment amount:");
            foreach ($mismatches as [$payment, $totalAllocated, $paymentAmount]) {
                $student = $payment->allocations->first()?->studentFeeItem?->student;
                $this->line("  Payment #{$payment->id} ({$payment->reference}) — {$student?->first_name} {$student?->last_name} — allocated {$totalAllocated} vs amount {$paymentAmount}");
            }
        }

        if ($crossEnrollment->isNotEmpty()) {
            $this->warn("{$crossEnrollment->count()} payment(s) split across DIFFERENT courses/enrollments — review for intent:");
            foreach ($crossEnrollment as $payment) {
                $student = $payment->allocations->first()?->studentFeeItem?->student;
                $this->line("  Payment #{$payment->id} ({$payment->reference}) — {$student?->first_name} {$student?->last_name} — amount " . number_format((float) $payment->amount, 2));

                $rows = $payment->allocations->map(function ($a) {
                    $item = $a->studentFeeItem;
                    return [
                        $item?->enrollment_id,
                        $item?->enrollment?->course?->title,
                        $item?->description,
                        number_format((float) $a->amount_allocated, 2),
                    ];
                });

                $this->table(['Enrollment #', 'Course', 'Fee Item', 'Allocated'], $rows);
            }
        }
    }
}
