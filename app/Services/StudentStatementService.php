<?php

namespace App\Services;

use App\Models\EnrollmentProgression;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\Payment;
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

            ->map(function ($entry) {

                $entry['sort_date'] = isset($entry['sort_date'])
                    ? Carbon::parse($entry['sort_date'])->timestamp
                    : 0;

                $entry['sort_order'] = (int) ($entry['sort_order'] ?? 0);

                $entry['sort_id'] = (int) ($entry['sort_id'] ?? 0);

                return $entry;
            })

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

        $chargeEntries = $this->chargeEntries(
            $student,
            $progression,
            $startDate,
            $endDate
        );

        $paymentEntries = $this->paymentEntries(
            $student,
            $progression,
            $startDate,
            $endDate
        );

        $debits = (float) $chargeEntries->sum('dr');

        $credits =
            (float) $chargeEntries->sum('cr') +
            (float) $paymentEntries->sum('cr');

        return (float) $opening + $debits - $credits;
    }

    protected function chargeEntries(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return $this->rawCharges($student, $progression, $startDate, $endDate)
            ->map(function (StudentFeeItem $item) {
                $amount = (float) $item->amount;

                return [
                    'date' => $item->charge_date,
                    'reference' => $amount < 0 ? 'DISC-' . $item->id : 'CHG-' . $item->id,
                    'description' => $item->description,
                    'dr' => $amount > 0 ? $amount : 0.00,
                    'cr' => $amount < 0 ? abs($amount) : 0.00,
                    'source_type' => $amount < 0 ? 'discount' : 'charge',
                    'sort_date' => optional($item->charge_date)->timestamp ?? 0,
                    'sort_order' => $amount < 0 ? 2 : 1,
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
        /* $payments = Payment::query()
            ->with([
                'allocations.studentFeeItem',
            ])
            ->where('student_id', $student->id)
            ->where(function ($q) use ($progression) {
                $q->where('enrollment_id', $progression->enrollment_id)
                    ->orWhereNull('enrollment_id');
            })
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
        return $payments->map(function (Payment $payment) use ($progression) {
            $paymentDate = Carbon::parse($payment->payment_date ?? $payment->paid_at);

            $currentProgressionAllocations = $payment->allocations
                ->filter(function ($allocation) use ($progression) {
                    return (int) $allocation->studentFeeItem?->enrollment_progression_id === (int) $progression->id;
                })
                ->map(function ($allocation) {
                    return [
                        'description' => $allocation->studentFeeItem?->description ?? 'Fee Item',
                        'amount' => (float) $allocation->amount_allocated,
                        'amount_allocated' => (float) $allocation->amount_allocated,
                    ];
                })
                ->values()
                ->all();

            return [
                'payment_id' => $payment->id,
                'date' => $paymentDate,
                'actual_payment_date' => $paymentDate,
                'reference' => $payment->transaction_id ?: ($payment->reference ?: 'PAY-' . $payment->id),
                'description' => 'Payment Received',
                'dr' => 0.00,
                'cr' => (float) $payment->amount,
                'source_type' => 'payment',
                'sort_date' => $paymentDate->timestamp,
                'sort_order' => 2,
                'sort_id' => $payment->id,

                'allocations' => $currentProgressionAllocations,
            ];
        })->values();*/

        $allocations = PaymentAllocation::query()
            ->with([
                'payment',
                'studentFeeItem',
            ])
            ->whereHas('studentFeeItem', function ($q) use ($student, $progression) {
                $q->where('student_id', $student->id)
                    ->where('enrollment_progression_id', $progression->id);
            })
            ->whereHas('payment')
            ->get();
        return $allocations
            ->groupBy('payment_id')
            ->map(function ($allocations) use ($startDate) {
                $firstAllocation = $allocations->first();
                $payment = $firstAllocation->payment;

                $paymentDate = Carbon::parse(
                    $payment->payment_date ?? $payment->paid_at
                );

                $effectiveLedgerDate = $allocations
                    ->map(function ($allocation) use ($paymentDate, $startDate) {
                        $feeItem = $allocation->studentFeeItem;

                        $chargeDate = $feeItem?->charge_date
                            ? Carbon::parse($feeItem->charge_date)
                            : $startDate;

                        return $paymentDate->lt($chargeDate)
                            ? $chargeDate
                            : $paymentDate;
                    })
                    ->min();

                $allocatedTotal = (float) $allocations->sum('amount_allocated');

                return [
                    'payment_id' => $payment->id,
                    'date' => $effectiveLedgerDate,
                    'actual_payment_date' => $paymentDate,
                    'reference' => $payment->transaction_id ?: ($payment->reference ?: 'PAY-' . $payment->id),
                    'description' => 'Payment Received',
                    'dr' => 0.00,
                    'cr' => $allocatedTotal,
                    'source_type' => 'payment',
                    'sort_date' => $effectiveLedgerDate->timestamp,
                    'sort_order' => 2,
                    'sort_id' => $payment->id,
                    'allocations' => $allocations
                        ->map(function ($allocation) {
                            return [
                                'description' => $allocation->studentFeeItem?->description ?? 'Fee Item',
                                'amount' => (float) $allocation->amount_allocated,
                                'amount_allocated' => (float) $allocation->amount_allocated,
                            ];
                        })
                        ->values()
                        ->all(),
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
            ->whereHas('payment', function ($q) use ($progression, $startDate, $endDate) {
                if ($this->isFirstProgression($progression)) {
                    $q->whereDate('payment_date', '<=', $endDate);
                } else {
                    $q->whereBetween('payment_date', [$startDate, $endDate]);
                }
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

    protected function isFirstProgression(EnrollmentProgression $progression): bool
    {
        return (int) $progression->trimester_sequence === 1;
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
