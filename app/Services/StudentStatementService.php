<?php

namespace App\Services;

use App\Models\Student;
use App\Models\EnrollmentProgression;
use App\Models\StudentFeeItem;
use App\Models\Payment;
use Carbon\Carbon;

class StudentStatementService
{
    public function buildProgressionStatement(Student $student, EnrollmentProgression $progression): array
    {
        if ((int) $progression->student_id !== (int) $student->id) {
            abort(404);
        }

        $progression->load(['trimester.academicYear', 'enrollment.course']);

        [$startDate, $endDate] = $this->progressionDates($progression);

        $openingBalance = $this->getOpeningBalance($student, $progression);

        $chargeEntries = $this->chargeEntries($student, $progression, $startDate, $endDate);
        $paymentEntries = $this->paymentEntries($student, $progression, $startDate, $endDate);

        $entries = $chargeEntries
            ->concat($paymentEntries)
            ->sortBy([
                ['sort_order', 'asc'],
                ['sort_date', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values();

        $runningBalance = $openingBalance;

        $ledger = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += (float) $entry['dr'];
            $runningBalance -= (float) $entry['cr'];

            $entry['balance'] = $runningBalance;

            return $entry;
        });

        return [
            'student' => $student,
            'progression' => $progression,
            'enrollment' => $progression->enrollment,
            'course' => $progression->enrollment?->course,
            'trimester' => $progression->trimester,
            'academic_year' => $progression->trimester?->academicYear,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'ledger' => $ledger,
            'total_debits' => $ledger->sum('dr'),
            'total_credits' => $ledger->sum('cr'),
            'closing_balance' => $runningBalance,
        ];
    }

    protected function getOpeningBalance(Student $student, EnrollmentProgression $progression): float
    {
        $previous = EnrollmentProgression::query()
            ->with(['trimester', 'enrollment.course'])
            ->where('student_id', $student->id)
            ->where('enrollment_id', $progression->enrollment_id)
            ->where('trimester_sequence', $progression->trimester_sequence - 1)
            ->first();

        if (!$previous) {
            return 0.00;
        }

        return $this->getClosingBalanceForProgression($student, $previous);
    }

    protected function getClosingBalanceForProgression(Student $student, EnrollmentProgression $progression): float
    {
        [$startDate, $endDate] = $this->progressionDates($progression);

        $opening = $this->getOpeningBalance($student, $progression);

        $debits = $this->chargeEntries($student, $progression, $startDate, $endDate)->sum('dr');
        $credits = $this->paymentEntries($student, $progression, $startDate, $endDate)->sum('cr');

        return (float) $opening + (float) $debits - (float) $credits;
    }

    protected function chargeEntries(Student $student, EnrollmentProgression $progression, Carbon $startDate, Carbon $endDate)
    {
        return StudentFeeItem::query()
            ->where('student_id', $student->id)
            ->where(function ($q) use ($progression, $startDate, $endDate) {
                $q->where('enrollment_progression_id', $progression->id)
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNull('enrollment_id')
                            ->whereNull('enrollment_progression_id')
                            ->whereBetween('charge_date', [$startDate, $endDate]);
                    });
            })
            ->orderByRaw('CASE WHEN enrollment_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('charge_date')
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->charge_date,
                    'reference' => 'CHG-' . $item->id,
                    'description' => $item->description,
                    'dr' => (float) $item->amount,
                    'cr' => 0.00,
                    'source_type' => 'charge',
                    'sort_date' => optional($item->charge_date)->timestamp ?? 0,
                    'sort_order' => is_null($item->enrollment_id) ? 1 : 2,
                    'sort_id' => $item->id,
                ];
            });
    }

    protected function paymentEntries(Student $student, EnrollmentProgression $progression, Carbon $startDate, Carbon $endDate)
    {
        return Payment::query()
            ->with(['allocations.studentFeeItem'])
            ->where('student_id', $student->id)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where(function ($q) use ($progression) {
                $q->whereNull('enrollment_id')
                    ->orWhere('enrollment_id', $progression->enrollment_id);
            })
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(function ($payment) {
                $allocations = $payment->allocations
                    ->groupBy(fn($allocation) => $allocation->studentFeeItem?->description ?? 'Fee Item')
                    ->map(function ($items, $description) {
                        return [
                            'description' => $description,
                            'amount' => (float) $items->sum('amount_allocated'),
                        ];
                    })
                    ->values();

                return [
                    'payment_id' => $payment->id,
                    'date' => $payment->paid_at ?? $payment->created_at,
                    'reference' => $payment->transaction_id ?: ($payment->reference ?? 'PAY-' . $payment->id),
                    'description' => 'Payment Received',
                    'dr' => 0.00,
                    'cr' => (float) $payment->amount,
                    'source_type' => 'payment',
                    'sort_date' => optional($payment->paid_at ?? $payment->created_at)->timestamp ?? 0,
                    'sort_order' => 3,
                    'sort_id' => $payment->id,
                    'allocations' => $allocations,
                ];
            });
    }

    protected function progressionDates(EnrollmentProgression $progression): array
    {
        $progression->loadMissing('trimester');

        return [
            Carbon::parse($progression->started_at ?? $progression->trimester?->start_date),
            Carbon::parse($progression->completed_at ?? $progression->trimester?->end_date),
        ];
    }
}
