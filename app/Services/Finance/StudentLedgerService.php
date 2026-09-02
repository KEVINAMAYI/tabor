<?php

namespace App\Services\Finance;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\StudentFeeItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentLedgerService
{
    /**
     * Return a lightweight statement for a deferred progression (no charges or payments).
     */
    public function buildDeferredProgressionStatement(Student $student, EnrollmentProgression $progression): array
    {
        $progression->loadMissing(['trimester.academicYear', 'enrollment.course', 'deferral']);

        [$startDate, $endDate] = $this->progressionDates($progression);

        return [
            'student'         => $student,
            'progression'     => $progression,
            'enrollment'      => $progression->enrollment,
            'course'          => $progression->enrollment?->course,
            'trimester'       => $progression->trimester,
            'academic_year'   => $progression->trimester?->academicYear,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'is_deferred'     => true,
            'deferral'        => $progression->deferral,
            'opening_balance' => 0.00,
            'charge_total'    => 0.00,
            'payment_total'   => 0.00,
            'closing_balance' => 0.00,
            'total_debits'    => 0.00,
            'total_credits'   => 0.00,
            'ledger'          => collect(),
        ];
    }

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
            'student'         => $student,
            'progression'     => $progression,
            'enrollment'      => $progression->enrollment,
            'course'          => $progression->enrollment?->course,
            'trimester'       => $progression->trimester,
            'academic_year'   => $progression->trimester?->academicYear,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'opening_balance' => $openingBalance,
            'charge_total'    => $ledger->sum('dr'),
            'payment_total'   => $ledger->sum('cr'),
            'closing_balance' => $runningBalance,
            'total_debits'    => $ledger->sum('dr'),
            'total_credits'   => $ledger->sum('cr'),
            'ledger'          => $ledger,
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
                $entry['sort_date']  = Carbon::parse($entry['date'])->timestamp;
                $entry['sort_order'] = (int) ($entry['sort_order'] ?? 0);
                $entry['sort_id']    = (int) ($entry['sort_id'] ?? 0);
                return $entry;
            })
            ->sortBy([
                ['sort_date', 'asc'],
                ['sort_order', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values();
    }

    /**
     * Build charge entries for the given progression.
     *
     * Waived items emit TWO entries — the original DR and an offsetting waiver CR —
     * so the balance trail is fully visible on the statement.
     */
    protected function chargeEntries(Student $student, EnrollmentProgression $progression): Collection
    {
        return StudentFeeItem::query()
            ->where('student_id', $student->id)
            ->where('enrollment_id', $progression->enrollment_id)
            ->where('enrollment_progression_id', $progression->id)
            ->orderBy('charge_date')
            ->orderBy('id')
            ->get()
            ->flatMap(function (StudentFeeItem $item) {
                $amount     = (float) $item->amount;
                $amountPaid = (float) $item->amount_paid;
                $balance    = (float) $item->balance;
                $creditType = $item->credit_type;

                // Detect any waiver component (full waiver or partial waiver)
                $isWaived = $item->status === 'waived' || $creditType === 'waiver';
                if ($isWaived) {
                    $expectedBalance = max(0, $amount - $amountPaid);
                    $waivedAmount    = max(0, $expectedBalance - $balance);

                    if ($waivedAmount > 0) {
                        return [
                            // Original charge
                            [
                                'date'          => Carbon::parse($item->charge_date ?? now()),
                                'reference'     => 'CHG-' . $item->id,
                                'description'   => $item->description,
                                'dr'            => $amount > 0 ? $amount : 0.00,
                                'cr'            => 0.00,
                                'source_type'   => 'charge',
                                'sort_order'    => 1,
                                'sort_id'       => $item->id,
                                'allocations'   => [],
                                'credit_type'   => null,
                                'applied_by'    => null,
                                'credit_reason' => null,
                            ],
                            // Waiver credit
                            [
                                'date'          => Carbon::parse($item->charge_date ?? now()),
                                'reference'     => 'WVR-' . $item->id,
                                'description'   => 'Waiver: ' . ($item->credit_reason ?: $item->description),
                                'dr'            => 0.00,
                                'cr'            => $waivedAmount,
                                'source_type'   => 'waiver',
                                'sort_order'    => 2,
                                'sort_id'       => $item->id,
                                'allocations'   => [],
                                'credit_type'   => 'waiver',
                                'applied_by'    => $item->applied_by,
                                'credit_reason' => $item->credit_reason,
                            ],
                        ];
                    }
                }

                // Credit items (discounts, scholarships posted as negative fee items — legacy)
                $isCredit = $amount < 0;

                $reference = match (true) {
                    $creditType === 'scholarship'     => 'SCHOL-' . $item->id,
                    $creditType === 'discount'        => 'DISC-' . $item->id,
                    $creditType === 'credit_transfer' => 'XFER-' . $item->id,
                    $isCredit                         => 'CR-' . $item->id,
                    default                           => 'CHG-' . $item->id,
                };

                $sourceType = match (true) {
                    $creditType !== null => $creditType,
                    $isCredit           => 'discount',
                    default             => 'charge',
                };

                return [[
                    'date'          => Carbon::parse($item->charge_date ?? now()),
                    'reference'     => $reference,
                    'description'   => $item->description,
                    'dr'            => $amount > 0 ? $amount : 0.00,
                    'cr'            => $amount < 0 ? abs($amount) : 0.00,
                    'source_type'   => $sourceType,
                    'sort_order'    => $isCredit ? 2 : 1,
                    'sort_id'       => $item->id,
                    'allocations'   => [],
                    'credit_type'   => $creditType,
                    'applied_by'    => $item->applied_by,
                    'credit_reason' => $item->credit_reason,
                ]];
            });
    }

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
        | Same-enrollment payments are owned by payment date — they belong to
        | whichever progression period the payment date falls into.
        */

        $dateOwnedPayments = Payment::query()
            ->with(['allocations.studentFeeItem'])
            ->where(function ($q) use ($student) {
                // Include payments with student_id directly, plus legacy payments
                // that only recorded enrollment_id (no student_id backfill yet).
                $q->where('student_id', $student->id)
                    ->orWhere(function ($sub) use ($student) {
                        $sub->whereNull('student_id')
                            ->whereHas('enrollment', fn($eq) => $eq->where('student_id', $student->id));
                    });
            })
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
        | A payment from a different enrollment that was allocated to fee items
        | in this progression (e.g. cross-enrollment credit transfers).
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
                $payment      = $dateOwnedPayments->get($paymentId);
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

                // For date-owned payments (date falls in this progression's period), show only
                // allocations to THIS progression's fee items — prevents a T4 payment from
                // showing T3 fee items when the payment was cross-allocated (data anomaly
                // corrected by `finance:reconcile --fix`).
                // For cross-enrollment payments, the $crossAllocations collection is already
                // filtered to this progression's items by the query above.
                $currentProgressionAllocations = $isDateOwned
                    ? $payment->allocations
                        ->filter(fn($allocation) =>
                            (int) optional($allocation->studentFeeItem)->enrollment_progression_id === (int) $progression->id
                        )
                        ->values()
                    : $crossAllocations;

                $visibleAllocations = $currentProgressionAllocations
                    ->filter(function ($allocation) use ($paymentDate) {
                        $feeItem   = $allocation->studentFeeItem;
                        $chargeDate = $feeItem?->charge_date
                            ? Carbon::parse($feeItem->charge_date)->startOfDay()
                            : null;
                        return !($chargeDate && $paymentDate->copy()->startOfDay()->lt($chargeDate));
                    })
                    ->values();

                if (
                    $isDateOwned &&
                    $this->isGermanChainProgression($progression) &&
                    $this->hasGermanPreviousEnrollmentBalance($student, $progression) &&
                    $paymentDate->lt($startDate)
                ) {
                    return null;
                }

                // Credit only what was actually allocated to THIS progression's
                // fee items, not the payment's raw amount — a payment sitting
                // unallocated (e.g. one that bypassed PaymentPostingService)
                // must not appear as applied money until it actually is one.
                // Deliberately $currentProgressionAllocations, NOT the more
                // narrowly filtered $visibleAllocations (which additionally
                // hides chronologically-anomalous allocations for display
                // purposes only, see the comment above) — the credited
                // amount must always match what actually reduced
                // StudentFeeItem.balance, or the statement's balance would
                // silently disagree with the real outstanding balance even
                // though its own sub-row breakdown looks incomplete for that
                // rare anomaly case.
                $creditAmount = (float) $currentProgressionAllocations->sum('amount_allocated');

                if ($creditAmount <= 0) {
                    return null;
                }

                $isCredit    = $payment->method === 'credit';
                $notes       = strtolower($payment->notes ?? '');
                $description = match (true) {
                    $isCredit && str_contains($notes, 'scholarship')     => 'Scholarship Credit',
                    $isCredit && str_contains($notes, 'discount')        => 'Discount Applied',
                    $isCredit && str_contains($notes, 'credit_transfer') => 'Credit Transfer',
                    $isCredit                                             => 'Credit Applied',
                    (bool) $payment->is_sponsored                        => 'Sponsored Payment — ' . $payment->sponsored_by,
                    default                                               => 'Payment Received',
                };

                return [
                    'payment_id'          => $payment->id,
                    'date'                => $paymentDate,
                    'actual_payment_date' => $paymentDate,
                    'reference'           =>
                        $payment->transaction_id
                        ?: $payment->reference
                        ?: $payment->receipt_no
                        ?: $payment->payer
                        ?: 'PAY-' . $payment->id,
                    'description'  => $description,
                    'dr'           => 0.00,
                    'cr'           => $creditAmount,
                    'source_type'  => $isCredit ? 'credit' : 'payment',
                    'is_sponsored' => (bool) $payment->is_sponsored,
                    'sponsored_by' => $payment->sponsored_by,
                    'method'       => $payment->method,
                    'sort_order'   => 3,
                    'sort_id'      => $payment->id,
                    'allocations'  => $visibleAllocations
                        ->map(fn($allocation) => [
                            'description'      => $allocation->studentFeeItem?->description ?? 'Fee Item',
                            'amount'           => (float) $allocation->amount_allocated,
                            'amount_allocated' => (float) $allocation->amount_allocated,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->sortBy([['date', 'asc'], ['sort_id', 'asc']])
            ->values();
    }

    /**
     * Calculate the opening balance for a progression.
     *
     * Checks in order:
     * 1. Explicit prerequisite_enrollment_id chain (admin-linked courses)
     * 2. German language course chain fallback (GLA1→GLA2→GLB1→GLB2)
     * 3. Same-enrollment prior progressions only
     */
    protected function openingBalance(Student $student, EnrollmentProgression $progression): float
    {
        $progression->loadMissing(['enrollment.course']);
        $enrollment = $progression->enrollment;

        // 1. Explicit prerequisite chain (takes precedence over hard-coded German chain)
        if (!empty($enrollment->prerequisite_enrollment_id)) {
            return $this->openingBalanceViaPrerequisiteChain($student, $progression);
        }

        // 2. German language chain fallback (for existing students without prerequisite_enrollment_id)
        $currentCourseCode = strtoupper(trim($enrollment?->course?->code ?? ''));
        $germanOrder = ['GLA1' => 1, 'GLA2' => 2, 'GLB1' => 3, 'GLB2' => 4];

        if (array_key_exists($currentCourseCode, $germanOrder)) {
            return $this->openingBalanceGermanChain($student, $progression, $currentCourseCode, $germanOrder);
        }

        // 3. Standard: same enrollment prior progressions
        $previousProgressions = EnrollmentProgression::query()
            ->where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id)
            ->where('trimester_sequence', '<', $progression->trimester_sequence)
            ->orderBy('trimester_sequence')
            ->get();

        return $this->calculateProgressionsBalance($student, $previousProgressions);
    }

    /**
     * Walk the explicitly linked prerequisite_enrollment_id chain.
     * Guards against circular references with a visited set.
     */
    protected function openingBalanceViaPrerequisiteChain(
        Student $student,
        EnrollmentProgression $progression
    ): float {
        $enrollment = $progression->enrollment;

        // Prior progressions in the current enrollment
        $sameEnrollmentPriors = EnrollmentProgression::query()
            ->where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id)
            ->where('trimester_sequence', '<', $progression->trimester_sequence)
            ->orderBy('trimester_sequence')
            ->get();

        $balance = $this->calculateProgressionsBalance($student, $sameEnrollmentPriors);

        // Walk up the chain
        $prereqId = $enrollment->prerequisite_enrollment_id;
        $visited  = [$enrollment->id];

        while ($prereqId && !in_array($prereqId, $visited, true)) {
            $visited[] = $prereqId;

            $prereqProgressions = EnrollmentProgression::query()
                ->where('student_id', $student->id)
                ->where('enrollment_id', $prereqId)
                ->orderBy('trimester_sequence')
                ->get();

            $balance += $this->calculateProgressionsBalance($student, $prereqProgressions);

            $prereqId = Enrollment::find($prereqId)?->prerequisite_enrollment_id;
        }

        return $balance;
    }

    /**
     * German language course chain opening balance.
     * Used as fallback when prerequisite_enrollment_id has not been set.
     */
    protected function openingBalanceGermanChain(
        Student $student,
        EnrollmentProgression $progression,
        string $currentCourseCode,
        array $germanOrder
    ): float {
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

        return $this->calculateProgressionsBalance($student, $previousProgressions);
    }

    protected function calculateProgressionsBalance(Student $student, Collection $progressions): float
    {
        $balance = 0.00;

        foreach ($progressions as $previousProgression) {
            [$startDate, $endDate] = $this->progressionDates($previousProgression);

            $entries = $this->ledgerEntries($student, $previousProgression, $startDate, $endDate);

            foreach ($entries as $entry) {
                $balance += (float) $entry['dr'];
                $balance -= (float) $entry['cr'];
            }
        }

        return $balance;
    }

    protected function progressionDates(EnrollmentProgression $progression): array
    {
        $progression->loadMissing(['trimester', 'enrollment.course']);

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

            $endDate = $startDate->copy()->addMonths($durationMonths)->subDay()->endOfDay();

            return [$startDate, $endDate];
        }

        $startDate = Carbon::parse(
            $progression->started_at ?? $progression->trimester?->start_date ?? now()
        )->startOfDay();

        $endDate = Carbon::parse(
            $progression->completed_at ?? $progression->trimester?->end_date ?? now()
        )->endOfDay();

        return [$startDate, $endDate];
    }

    protected function isPreviousGermanCoursePayment(Payment $payment, EnrollmentProgression $progression): bool
    {
        $progression->loadMissing(['enrollment.course']);
        $payment->loadMissing(['enrollment.course']);

        $order = ['GLA1' => 1, 'GLA2' => 2, 'GLB1' => 3, 'GLB2' => 4];

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

    protected function hasGermanPreviousEnrollmentBalance(Student $student, EnrollmentProgression $progression): bool
    {
        $progression->loadMissing(['enrollment.course']);

        $currentCode = strtoupper(trim($progression->enrollment?->course?->code ?? ''));
        $order = ['GLA1' => 1, 'GLA2' => 2, 'GLB1' => 3, 'GLB2' => 4];

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
}
