<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Trimester;
use App\Models\StudentFeeItem;
use App\Models\PaymentAllocation;
use Illuminate\Support\Collection;

class StudentStatementService
{
    public function buildTrimesterStatement(
        Student $student,
        Trimester $trimester,
        ?int $enrollmentId = null
    ): array {
        $chargesQuery = StudentFeeItem::query()
            ->where('student_id', $student->id);

        if ($enrollmentId) {
            $chargesQuery->where(function ($q) use ($enrollmentId) {
                $q->where('enrollment_id', $enrollmentId)
                    ->orWhereNull('enrollment_id');
            });
        }

        $allRelevantCharges = (clone $chargesQuery)->get();

        $openingBalance = $allRelevantCharges
            ->filter(fn ($item) => $item->charge_date && $item->charge_date->lt($trimester->start_date))
            ->sum(fn ($item) => (float) $item->amount - (float) $item->amount_paid);

        $chargeEntries = (clone $chargesQuery)
            ->with(['trimester', 'feeDefinition', 'enrollment.course'])
            ->where('trimester_id', $trimester->id)
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->charge_date,
                    'reference' => 'CHG-' . $item->id,
                    'description' => $item->description,
                    'dr' => (float) $item->amount,
                    'cr' => 0.00,
                    'source_type' => 'charge',
                    'sort_date' => $item->charge_date?->timestamp ?? 0,
                    'sort_id' => $item->id,
                ];
            });

        $allocationQuery = PaymentAllocation::query()
            ->whereHas('studentFeeItem', function ($q) use ($student, $enrollmentId) {
                $q->where('student_id', $student->id);

                if ($enrollmentId) {
                    $q->where(function ($sub) use ($enrollmentId) {
                        $sub->where('enrollment_id', $enrollmentId)
                            ->orWhereNull('enrollment_id');
                    });
                }
            })
            ->whereHas('payment', function ($q) use ($trimester) {
                $q->whereBetween('payment_date', [$trimester->start_date, $trimester->end_date]);
            });

        $paymentEntries = $allocationQuery
            ->with(['payment', 'studentFeeItem'])
            ->get()
            ->map(function ($allocation) {
                $payment = $allocation->payment;

                return [
                    'date' => $payment?->payment_date,
                    'reference' => $payment?->receipt_no ?: ($payment?->reference_no ?: 'PAY-' . $allocation->id),
                    'description' => 'Payment - ' . ($allocation->studentFeeItem?->description ?? 'Allocated Payment'),
                    'dr' => 0.00,
                    'cr' => (float) $allocation->amount_allocated,
                    'source_type' => 'payment',
                    'sort_date' => $payment?->payment_date?->timestamp ?? 0,
                    'sort_id' => $allocation->id,
                ];
            });

        $entries = $chargeEntries
            ->concat($paymentEntries)
            ->sortBy([
                ['sort_date', 'asc'],
                ['source_type', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values();

        $runningBalance = (float) $openingBalance;

        $ledger = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += (float) $entry['dr'];
            $runningBalance -= (float) $entry['cr'];

            $entry['balance'] = $runningBalance;

            return $entry;
        });

        return [
            'student' => $student,
            'trimester' => $trimester,
            'enrollment_id' => $enrollmentId,
            'opening_balance' => (float) $openingBalance,
            'ledger' => $ledger,
            'total_debits' => $ledger->sum('dr'),
            'total_credits' => $ledger->sum('cr'),
            'closing_balance' => (float) $runningBalance,
        ];
    }
}