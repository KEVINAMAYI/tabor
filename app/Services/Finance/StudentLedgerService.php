<?php

namespace App\Services\Finance;

use App\Models\EnrollmentProgression;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\StudentFeeItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentLedgerService
{
    public function buildProgressionStatement(Student $student, EnrollmentProgression $progression): array
    {
        $progression->loadMissing([
            'trimester.academicYear',
            'enrollment.course',
        ]);

        [$startDate, $endDate] = $this->progressionDates($progression);

        $openingBalance = $this->openingBalance($student, $progression);

        $entries = $this->ledgerEntries($student, $progression, $startDate, $endDate);

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
        ];
    }

    protected function ledgerEntries(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return $this->chargeEntries($student, $progression)
            ->concat($this->paymentEntries($student, $progression, $startDate, $endDate))
            ->map(function (array $entry) {
                $entry['sort_date'] = Carbon::parse($entry['date'])->timestamp;
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
    }

    protected function chargeEntries(Student $student, EnrollmentProgression $progression): Collection
    {
        return StudentFeeItem::query()
            ->where('student_id', $student->id)
            ->where('enrollment_id', $progression->enrollment_id)
            ->where('enrollment_progression_id', $progression->id)
            ->orderBy('charge_date')
            ->orderBy('id')
            ->get()
            ->map(function (StudentFeeItem $item) {
                $amount = (float) $item->amount;

                return [
                    'date' => Carbon::parse($item->charge_date ?? now()),
                    'reference' => $amount < 0 ? 'DISC-' . $item->id : 'CHG-' . $item->id,
                    'description' => $item->description,
                    'dr' => $amount > 0 ? $amount : 0.00,
                    'cr' => $amount < 0 ? abs($amount) : 0.00,
                    'source_type' => $amount < 0 ? 'discount' : 'charge',
                    'sort_order' => $amount < 0 ? 2 : 1,
                    'sort_id' => $item->id,
                    'allocations' => [],
                ];
            });
    }

    /* protected function paymentEntries(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $payments = Payment::query()
            ->with(['allocations.studentFeeItem'])
            ->where('student_id', $student->id)
            ->where(function ($q) use ($progression) {
                $q->where('enrollment_id', $progression->enrollment_id)
                    ->orWhereNull('enrollment_id');
            })

            ->when(
                !$this->isFirstProgression($progression),
                fn($q) => $q->whereDate('payment_date', '>=', $startDate->toDateString())
            )

            ->when(
                !$this->isFinalProgression($progression),
                fn($q) => $q->whereDate('payment_date', '<=', $endDate->toDateString())
            )

            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        return $payments->map(function (Payment $payment) use ($progression) {
            $paymentDate = Carbon::parse(
                $payment->payment_date ?? $payment->paid_at ?? now()
            );

            $progressionAllocations = $payment->allocations
                ->filter(function ($allocation) use ($progression, $paymentDate) {
                    $feeItem = $allocation->studentFeeItem;

                    if (!$feeItem) {
                        return false;
                    }

                    if ((int) $feeItem->enrollment_progression_id !== (int) $progression->id) {
                        return false;
                    }

                    $chargeDate = $feeItem->charge_date
                        ? Carbon::parse($feeItem->charge_date)
                        : null;

                    if ($chargeDate && $paymentDate->lt($chargeDate)) {
                        return false;
                    }

                    return true;
                })
                ->values();

            return [
                'payment_id' => $payment->id,
                'date' => $paymentDate,
                'actual_payment_date' => $paymentDate,
                'reference' =>
                    $payment->transaction_id
                    ?: $payment->reference
                    ?: $payment->receipt_no
                    ?: $payment->payer
                    ?: 'PAY-' . $payment->id,
                'description' => 'Payment Received',
                'dr' => 0.00,
                'cr' => (float) $payment->amount,
                'source_type' => 'payment',
                'sort_order' => 3,
                'sort_id' => $payment->id,
                'allocations' => $progressionAllocations
                    ->map(function ($allocation) {
                        return [
                            'description' => $allocation->studentFeeItem?->description ?? 'Fee Item',
                            'amount' => (float) $allocation->amount_allocated,
                            'amount_allocated' => (float) $allocation->amount_allocated,
                        ];
                    })
                    ->all(),
            ];
        })->values();
    } */

    protected function paymentEntries(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $previousProgression = EnrollmentProgression::query()
            ->where('student_id', $student->id)
            ->where('enrollment_id', $progression->enrollment_id)
            ->where('trimester_sequence', '<', $progression->trimester_sequence)
            ->orderByDesc('trimester_sequence')
            ->first();

        $previousEndDate = null;

        if ($previousProgression) {
            [, $previousEndDate] = $this->progressionDates($previousProgression);
            $previousEndDate = $previousEndDate->copy()->endOfDay();
        }

        $dateBelongsToThisStatement = function (Payment $payment) use ($previousEndDate, $endDate, $progression): bool {
            $paymentDate = Carbon::parse(
                $payment->payment_date ?? $payment->paid_at ?? now()
            )->startOfDay();

            if ($previousEndDate && $paymentDate->lte($previousEndDate)) {
                return false;
            }

            if (
                !$this->isFinalProgression($progression) &&
                $paymentDate->gt($endDate->copy()->endOfDay())
            ) {
                return false;
            }

            return true;
        };

        /*
        |--------------------------------------------------------------------------
        | Date-owned payments
        |--------------------------------------------------------------------------
        |
        | Same-enrollment payments are owned by payment date.
        | They must not be dragged into another progression by allocations.
        |
        */

        $dateOwnedPayments = Payment::query()
            ->with(['allocations.studentFeeItem'])
            ->where('student_id', $student->id)
            ->where(function ($q) use ($progression) {
                $q->where('enrollment_id', $progression->enrollment_id)
                    ->orWhereNull('enrollment_id');
            })
            ->get()
            ->filter(fn(Payment $payment) => $dateBelongsToThisStatement($payment))
            ->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | Cross-enrollment allocation payments
        |--------------------------------------------------------------------------
        |
        | Only pull a payment by allocation if the payment belongs to another
        | enrollment or has no enrollment. Same-enrollment payments are handled
        | strictly by date above.
        |
        */

        $crossEnrollmentAllocationGroups = PaymentAllocation::query()
            ->with([
                'payment.allocations.studentFeeItem',
                'studentFeeItem',
            ])
            ->whereHas('studentFeeItem', function ($q) use ($student, $progression) {
                $q->where('student_id', $student->id)
                    ->where('enrollment_id', $progression->enrollment_id)
                    ->where('enrollment_progression_id', $progression->id);
            })
            ->whereHas('payment', function ($q) use ($progression) {
                $q->where(function ($paymentQuery) use ($progression) {
                    $paymentQuery
                        ->whereNull('enrollment_id')
                        ->orWhere('enrollment_id', '!=', $progression->enrollment_id);
                });
            })
            ->get()
            ->groupBy('payment_id');

        $paymentIds = $dateOwnedPayments
            ->keys()
            ->merge($crossEnrollmentAllocationGroups->keys())
            ->unique()
            ->values();

        return $paymentIds
            ->map(function ($paymentId) use ($dateOwnedPayments, $crossEnrollmentAllocationGroups, $progression, $student, $startDate) {
                $payment = $dateOwnedPayments->get($paymentId);

                $crossAllocations = collect();

                if ($crossEnrollmentAllocationGroups->has($paymentId)) {
                    $crossAllocations = $crossEnrollmentAllocationGroups->get($paymentId);
                    $payment = $crossAllocations->first()?->payment;
                }

                if (!$payment) {
                    return null;
                }

                $isDateOwned = $dateOwnedPayments->has($payment->id);

                if (
                    !$isDateOwned &&
                    $this->isPreviousGermanCoursePayment($payment, $progression)
                ) {
                    return null;
                }

                $payment->loadMissing(['allocations.studentFeeItem']);

                $paymentDate = Carbon::parse(
                    $payment->payment_date ?? $payment->paid_at ?? now()
                );

                $currentProgressionAllocations = $payment->allocations
                    ->filter(function ($allocation) use ($progression) {
                        return (int) optional($allocation->studentFeeItem)->enrollment_progression_id
                            === (int) $progression->id;
                    })
                    ->values();

                $visibleAllocations = $currentProgressionAllocations
                    ->filter(function ($allocation) use ($paymentDate) {
                        $feeItem = $allocation->studentFeeItem;

                        if (!$feeItem) {
                            return false;
                        }

                        $chargeDate = $feeItem->charge_date
                            ? Carbon::parse($feeItem->charge_date)->startOfDay()
                            : null;

                        return !($chargeDate && $paymentDate->copy()->startOfDay()->lt($chargeDate));
                    })
                    ->values();

                $creditAmount = 0.00;

                if ($isDateOwned) {
                    /*
                    |--------------------------------------------------------------------------
                    | German Chain Carry-Forward Protection
                    |--------------------------------------------------------------------------
                    |
                    | For German courses GLA1 -> GLA2 -> GLB1 -> GLB2, if this statement already
                    | has a negative opening balance brought forward from a previous German
                    | course, do not show the same old payment again.
                    |
                    */

                    if (
                        $this->isGermanChainProgression($progression) &&
                        $this->hasGermanPreviousEnrollmentBalance($student, $progression) &&
                        $paymentDate->lt($startDate)
                    ) {
                        return null;
                    }

                    $creditAmount = (float) $payment->amount;
                } else {
                    $creditAmount = (float) $crossAllocations->sum('amount_allocated');
                }

                if ($creditAmount <= 0) {
                    return null;
                }

                return [
                    'payment_id' => $payment->id,
                    'date' => $paymentDate,
                    'actual_payment_date' => $paymentDate,
                    'reference' =>
                        $payment->transaction_id
                        ?: $payment->reference
                        ?: $payment->receipt_no
                        ?: $payment->payer
                        ?: 'PAY-' . $payment->id,
                    'description' => 'Payment Received',
                    'dr' => 0.00,
                    'cr' => $creditAmount,
                    'source_type' => 'payment',
                    'sort_order' => 3,
                    'sort_id' => $payment->id,
                    'allocations' => $visibleAllocations
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
            ->filter()
            ->sortBy([
                ['date', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values();
    }

    protected function isPreviousGermanCoursePayment(
        Payment $payment,
        EnrollmentProgression $progression
    ): bool {
        $progression->loadMissing(['enrollment.course']);
        $payment->loadMissing(['enrollment.course']);

        $order = [
            'GLA1' => 1,
            'GLA2' => 2,
            'GLB1' => 3,
            'GLB2' => 4,
        ];

        $currentCode = strtoupper(trim($progression->enrollment?->course?->code ?? ''));
        $paymentCode = strtoupper(trim($payment->enrollment?->course?->code ?? ''));

        if (!isset($order[$currentCode], $order[$paymentCode])) {
            return false;
        }

        return $order[$paymentCode] < $order[$currentCode];
    }

    protected function isGermanChainProgression(EnrollmentProgression $progression): bool
    {
        $progression->loadMissing(['enrollment.course']);

        return in_array(
            strtoupper(trim($progression->enrollment?->course?->code ?? '')),
            ['GLA1', 'GLA2', 'GLB1', 'GLB2'],
            true
        );
    }

    protected function hasGermanPreviousEnrollmentBalance(
        Student $student,
        EnrollmentProgression $progression
    ): bool {
        $progression->loadMissing(['enrollment.course']);

        $currentCode = strtoupper(trim($progression->enrollment?->course?->code ?? ''));

        $order = [
            'GLA1' => 1,
            'GLA2' => 2,
            'GLB1' => 3,
            'GLB2' => 4,
        ];

        if (!isset($order[$currentCode])) {
            return false;
        }

        return EnrollmentProgression::query()
            ->where('student_id', $student->id)
            ->whereHas('enrollment.course', function ($q) use ($order, $currentCode) {
                $q->whereIn('code', array_keys($order))
                    ->whereIn(
                        'code',
                        collect($order)
                            ->filter(fn($rank) => $rank < $order[$currentCode])
                            ->keys()
                            ->all()
                    );
            })
            ->exists();
    }

    protected function isFirstProgression(EnrollmentProgression $progression): bool
    {
        return (int) $progression->trimester_sequence === 1;
    }

    protected function isFinalProgression(EnrollmentProgression $progression): bool
    {
        return !EnrollmentProgression::query()
            ->where('enrollment_id', $progression->enrollment_id)
            ->where('trimester_sequence', '>', $progression->trimester_sequence)
            ->exists();
    }
    protected function allocatedPaymentsForProgression(
        Student $student,
        EnrollmentProgression $progression
    ): Collection {
        return PaymentAllocation::query()
            ->with([
                'payment',
                'studentFeeItem',
            ])
            ->whereHas('studentFeeItem', function ($q) use ($student, $progression) {
                $q->where('student_id', $student->id)
                    ->where('enrollment_id', $progression->enrollment_id)
                    ->where('enrollment_progression_id', $progression->id);
            })
            ->whereHas('payment')
            ->get()
            ->groupBy('payment_id')
            ->map(function (Collection $allocations) {
                $payment = $allocations->first()->payment;

                return [
                    'payment' => $payment,
                    'amount' => (float) $allocations->sum('amount_allocated'),
                    'allocations' => $allocations,
                ];
            });
    }

    protected function unallocatedPaymentsByDate(
        Student $student,
        EnrollmentProgression $progression,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return Payment::query()
            ->where('student_id', $student->id)
            ->where(function ($q) use ($progression) {
                $q->where('enrollment_id', $progression->enrollment_id)
                    ->orWhereNull('enrollment_id');
            })
            ->where('unallocated_balance', '>', 0)
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
    }

    /* protected function openingBalance(Student $student, EnrollmentProgression $progression): float
    {
        $previousProgressions = EnrollmentProgression::query()
            ->where('student_id', $student->id)
            ->where('enrollment_id', $progression->enrollment_id)
            ->where('trimester_sequence', '<', $progression->trimester_sequence)
            ->orderBy('trimester_sequence')
            ->get();

        $balance = 0.00;

        foreach ($previousProgressions as $previousProgression) {
            [$startDate, $endDate] = $this->progressionDates($previousProgression);

            $entries = $this->ledgerEntries($student, $previousProgression, $startDate, $endDate);

            foreach ($entries as $entry) {
                $balance += (float) $entry['dr'];
                $balance -= (float) $entry['cr'];
            }
        }

        return $balance;
    } */

    protected function openingBalance(Student $student, EnrollmentProgression $progression): float
    {
        $progression->loadMissing(['enrollment.course']);

        $currentCourseCode = strtoupper(trim($progression->enrollment?->course?->code ?? ''));

        $germanOrder = [
            'GLA1' => 1,
            'GLA2' => 2,
            'GLB1' => 3,
            'GLB2' => 4,
        ];

        if (!array_key_exists($currentCourseCode, $germanOrder)) {
            $previousProgressions = EnrollmentProgression::query()
                ->where('student_id', $student->id)
                ->where('enrollment_id', $progression->enrollment_id)
                ->where('trimester_sequence', '<', $progression->trimester_sequence)
                ->orderBy('trimester_sequence')
                ->get();

            return $this->calculateProgressionsBalance(
                $student,
                $previousProgressions
            );
        }

        $currentGermanRank = $germanOrder[$currentCourseCode];

        $previousProgressions = EnrollmentProgression::query()
            ->with(['enrollment.course'])
            ->where('student_id', $student->id)
            ->whereHas('enrollment.course', function ($q) use ($germanOrder) {
                $q->whereIn('code', array_keys($germanOrder));
            })
            ->get()
            ->filter(function (EnrollmentProgression $item) use ($progression, $germanOrder, $currentGermanRank) {
                $code = strtoupper(trim($item->enrollment?->course?->code ?? ''));

                if (!array_key_exists($code, $germanOrder)) {
                    return false;
                }

                $rank = $germanOrder[$code];

                if ($rank < $currentGermanRank) {
                    return true;
                }

                if (
                    $rank === $currentGermanRank &&
                    (int) $item->enrollment_id === (int) $progression->enrollment_id &&
                    (int) $item->trimester_sequence < (int) $progression->trimester_sequence
                ) {
                    return true;
                }

                return false;
            })
            ->sortBy(function (EnrollmentProgression $item) use ($germanOrder) {
                $code = strtoupper(trim($item->enrollment?->course?->code ?? ''));

                return sprintf(
                    '%02d-%s-%04d',
                    $germanOrder[$code] ?? 99,
                    optional($item->started_at)->format('Ymd') ?: '99999999',
                    $item->trimester_sequence
                );
            })
            ->values();

        return $this->calculateProgressionsBalance(
            $student,
            $previousProgressions
        );
    }

    protected function calculateProgressionsBalance(
        Student $student,
        Collection $progressions
    ): float {
        $balance = 0.00;

        foreach ($progressions as $previousProgression) {
            [$startDate, $endDate] = $this->progressionDates($previousProgression);

            $entries = $this->ledgerEntries(
                $student,
                $previousProgression,
                $startDate,
                $endDate
            );

            foreach ($entries as $entry) {
                $balance += (float) $entry['dr'];
                $balance -= (float) $entry['cr'];
            }
        }

        return $balance;
    }

    protected function progressionDates(EnrollmentProgression $progression): array
    {
        $progression->loadMissing([
            'trimester',
            'enrollment.course',
        ]);

        $course = $progression->enrollment?->course;

        if ((bool) $course?->allows_continuous_intake) {
            $startDate = Carbon::parse(
                $progression->started_at
                ?? $progression->enrollment?->admission_date
                ?? $progression->trimester?->start_date
                ?? now()
            )->startOfDay();

            $durationMonths = match (true) {
                str_contains(strtolower($course?->title ?? ''), 'b1') => 4,
                str_contains(strtolower($course?->title ?? ''), 'b2') => 4,
                default => 3,
            };

            $endDate = Carbon::parse(
                $progression->completed_at
            ) ??
                $startDate
                    ->copy()
                    ->addMonths($durationMonths)
                    ->subDay()
                    ->endOfDay();

            return [$startDate, $endDate];
        }

        $startDate = Carbon::parse(
            $progression->started_at
            ?? $progression->trimester?->start_date
            ?? now()
        )->startOfDay();

        $endDate = Carbon::parse(
            $progression->completed_at
            ?? $progression->trimester?->end_date
            ?? now()
        )->endOfDay();

        return [$startDate, $endDate];
    }
}
