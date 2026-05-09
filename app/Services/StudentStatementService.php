<?php

namespace App\Services;

use App\Models\EnrollmentProgression;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\Trimester;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentStatementService
{
    public function buildProgressionStatement(Student $student, EnrollmentProgression $progression): array
    {
        if ((int) $progression->student_id !== (int) $student->id) {
            abort(404);
        }

        $progression->loadMissing([
            'trimester.academicYear',
            'enrollment.course',
        ]);

        [$startDate, $endDate] = $this->progressionDates($progression);

        $openingBalance = $this->getOpeningBalance($student, $progression);

        $chargeEntries = $this->chargeEntries($student, $progression, $startDate, $endDate);
        $paymentEntries = $this->paymentEntries($student, $progression, $startDate, $endDate);

        $entries = $chargeEntries
            ->concat($paymentEntries)
            ->sortBy([
                ['sort_date', 'asc'],
                ['sort_order', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values();

        $runningBalance = $openingBalance;

        $ledger = $entries->map(function (array $entry) use (&$runningBalance) {
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
            'charge_total' => $ledger->sum('dr'),
            'payment_total' => $ledger->sum('cr'),
            'closing_balance' => $runningBalance,

            'total_debits' => $ledger->sum('dr'),
            'total_credits' => $ledger->sum('cr'),

            'ledger' => $ledger,
            'charges' => $this->rawCharges($student, $progression, $startDate, $endDate),
            'allocations' => $this->rawAllocations($student, $progression, $startDate, $endDate),
        ];
    }

    public function buildTrimesterStatement(Student $student, Trimester $trimester, ?int $enrollmentId = null): array
    {
        $progression = EnrollmentProgression::query()
            ->where('student_id', $student->id)
            ->where('trimester_id', $trimester->id)
            ->when($enrollmentId, fn($q) => $q->where('enrollment_id', $enrollmentId))
            ->with(['trimester.academicYear', 'enrollment.course'])
            ->orderByDesc('id')
            ->firstOrFail();

        return $this->buildProgressionStatement($student, $progression);
    }

    protected function getOpeningBalance(Student $student, EnrollmentProgression $progression): float
    {
        $previous = EnrollmentProgression::query()
            ->where('student_id', $student->id)
            ->where('enrollment_id', $progression->enrollment_id)
            ->where('trimester_sequence', '<', $progression->trimester_sequence)
            ->orderByDesc('trimester_sequence')
            ->orderByDesc('id')
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

        $debits = $this->chargeEntries($student, $progression, $startDate, $endDate)
            ->sum('dr');

        $credits = $this->paymentEntries($student, $progression, $startDate, $endDate)
            ->sum('cr');

        return (float) $opening + (float) $debits - (float) $credits;
    }

    protected function chargeEntries(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return $this->rawCharges($student, $progression, $startDate, $endDate)
            ->map(function (StudentFeeItem $item) {
                return [
                    'date' => $item->charge_date,
                    'reference' => 'CHG-' . $item->id,
                    'description' => $item->description,
                    'dr' => (float) $item->amount,
                    'cr' => 0.00,
                    'source_type' => 'charge',
                    'sort_date' => optional($item->charge_date)->timestamp ?? 0,
                    'sort_order' => 1,
                    'sort_id' => $item->id,
                ];
            });
    }

    protected function paymentEntries(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return $this->rawAllocations($student, $progression, $startDate, $endDate)
            ->groupBy('payment_id')
            ->map(function (Collection $allocations) {
                $payment = $allocations->first()->payment;

                $breakdown = $allocations
                    ->groupBy(fn($allocation) => $allocation->studentFeeItem?->description ?? 'Fee Item')
                    ->map(function (Collection $items, string $description) {
                        return [
                            'description' => $description,
                            'amount' => (float) $items->sum('amount_allocated'),
                        ];
                    })
                    ->values();

                return [
                    'payment_id' => $payment->id,
                    'date' => $payment->payment_date,
                    'reference' => $payment->receipt_no ?: ($payment->reference ?: 'PAY-' . $payment->id),
                    'description' => 'Payment Received',
                    'dr' => 0.00,
                    'cr' => (float) $allocations->sum('amount_allocated'),
                    'source_type' => 'payment',
                    'sort_date' => optional($payment->payment_date)->timestamp ?? 0,
                    'sort_order' => 2,
                    'sort_id' => $payment->id,
                    'allocations' => $breakdown,
                ];
            })
            ->values();
    }

    protected function rawCharges(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return StudentFeeItem::query()
            ->where('student_id', $student->id)
            ->where(function ($q) use ($progression, $startDate, $endDate) {
                $q->where('enrollment_progression_id', $progression->id)
                    ->orWhere(function ($sub) use ($progression, $startDate, $endDate) {
                        $sub->where('enrollment_id', $progression->enrollment_id)
                            ->whereNull('enrollment_progression_id')
                            ->whereBetween('charge_date', [$startDate, $endDate]);
                    });
            })
            ->orderBy('charge_date')
            ->orderBy('id')
            ->get();
    }

    protected function rawAllocations(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return PaymentAllocation::query()
            ->with(['payment', 'studentFeeItem'])
            ->whereHas('studentFeeItem', function ($q) use ($student, $progression) {
                $q->where('student_id', $student->id)
                    ->where('enrollment_id', $progression->enrollment_id);
            })
            ->whereHas('payment', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('payment_date', [$startDate, $endDate]);
            })
            ->orderBy(
                PaymentAllocation::query()
                    ->select('payments.payment_date')
                    ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
                    ->whereColumn('payment_allocations.payment_id', 'payments.id')
                    ->limit(1)
            )
            ->orderBy('id')
            ->get();
    }

    protected function progressionDates(EnrollmentProgression $progression): array
    {
        $progression->loadMissing('trimester');

        return [
            Carbon::parse($progression->started_at ?? $progression->trimester?->start_date)->startOfDay(),
            Carbon::parse($progression->completed_at ?? $progression->trimester?->end_date)->endOfDay(),
        ];
    }
}
