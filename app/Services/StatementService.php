<?php

namespace App\Services;

use App\Models\AdminFeeItem;
use App\Models\Enrollment;
use App\Models\EnrollmentTrimester;
use App\Models\Payment;
use Carbon\Carbon;

class StatementService
{
    /**
     * Build statement data for ONE trimester of an enrollment.
     *
     * Returns data shaped for your Blade template:
     * - enrollment
     * - trimester
     * - count (total trimesters)
     * - openingBalance (balance b/f)
     * - ledger (rows within trimester only; running balance computed in view)
     * - statementNo
     * - statementDate
     */
    public static function buildTrimesterStatement(int $enrollmentId, int $trimesterNumber): array
    {
        $enrollment = Enrollment::with(['student.user', 'student', 'course', 'intake', 'trimesters'])
            ->findOrFail($enrollmentId);

        $trimesters = $enrollment->trimesters()->orderBy('trimester_number')->get();
        $count = $trimesters->count();

        /** @var EnrollmentTrimester|null $trimester */
        $trimester = $trimesters->firstWhere('trimester_number', $trimesterNumber);
        if (!$trimester) {
            throw new \LogicException("Trimester {$trimesterNumber} not found for enrollment #{$enrollmentId}.");
        }

        $start = Carbon::parse($trimester->start_date)->startOfDay();
        $end   = Carbon::parse($trimester->end_date)->endOfDay();

        // 1) Build ALL entries up to trimester END (needed to compute opening/closing)
        $allEntriesUpToEnd = collect()
            ->merge(self::makeAllChargeEntriesUpToEnd($enrollment, $end))
            ->merge(self::makePaymentEntriesUpToEnd($enrollment->id, $end))
            ->sortBy(fn($r) => Carbon::parse($r['date'])->timestamp)
            ->values();

        // 2) Compute opening balance = running balance BEFORE trimester start
        $openingBalance = self::computeOpeningBalance($allEntriesUpToEnd, $start);

        // 3) Ledger rows to DISPLAY = only rows within trimester period
        //    (Your Blade computes running balance starting from openingBalance)
        $ledger = $allEntriesUpToEnd->filter(function ($r) use ($start, $end) {
            $d = Carbon::parse($r['date']);
            return $d->betweenIncluded($start, $end);
        })->values()->all();

        // Statement meta
        $statementNo = 'STMT-' . str_pad((int) $enrollment->id, 5, '0', STR_PAD_LEFT) . '-T' . $trimesterNumber;
        $statementDate = now()->format('d M Y');

        return compact(
            'enrollment',
            'trimester',
            'count',
            'openingBalance',
            'ledger',
            'statementNo',
            'statementDate'
        );
    }

    /**
     * Build charge entries (tuition per trimester + optional admin fees) up to a given date.
     * Charges are NOT persisted, just generated for statements.
     */
    private static function makeAllChargeEntriesUpToEnd(Enrollment $enrollment, Carbon $end): array
    {
        $rows = [];

        // A) Tuition charges PER trimester (the core requirement)
        $trimesters = $enrollment->trimesters()->orderBy('trimester_number')->get();

        foreach ($trimesters as $t) {
            $chargeDate = Carbon::parse($t->start_date)->startOfDay();

            // only include charges up to requested end date (so opening calc is correct)
            if ($chargeDate->gt($end)) continue;

            $rows[] = [
                'date' => $chargeDate->toDateString(),
                'type' => 'charge',
                'amount' => (float) $t->fee_amount,
                'description' => 'Tuition Fee - Trimester ' . $t->trimester_number,
                'meta' => [
                    'kind' => 'tuition',
                    'trimester_number' => $t->trimester_number,
                    'enrollment_trimester_id' => $t->id,
                ],
            ];
        }

        // B) Optional student-bound admin fees (only if booleans on enrollment say "include")
        //    These are charged once; we place them on enrollment date (or trimester 1 start).
        $rows = array_merge($rows, self::makeAdminFeeChargeEntries($enrollment, $end));

        return $rows;
    }

    /**
     * Generate admin fee charge entries (one-time) if included in this enrollment.
     * Expected amounts come from AdminFeeItem; paid amounts are from students table.
     * Charge amount = max(0, expected - paid).
     */
    private static function makeAdminFeeChargeEntries(Enrollment $enrollment, Carbon $end): array
    {
        $student = $enrollment->student;

        // Put admin fee charges on enrollment date (so they appear in trimester 1 statement naturally)
        $chargeDate = Carbon::parse($enrollment->enrolled_at ?? $enrollment->created_at)->startOfDay();
        if ($chargeDate->gt($end)) return [];

        $map = [
            [
                'include_field' => 'include_registration_fee',
                'paid_field' => 'registration_fee',
                'code' => 'REGISTRATION',
                'fallback' => 'Registration Fee',
            ],
            [
                'include_field' => 'include_student_id_fee',
                'paid_field' => 'student_id_fee',
                'code' => 'STUDENT_ID',
                'fallback' => 'Student ID Fee',
            ],
            [
                'include_field' => 'include_stationery_fee',
                'paid_field' => 'stationery_fee',
                'code' => 'STATIONERY',
                'fallback' => 'Stationery Fee',
            ],
            [
                'include_field' => 'include_caution_fee',
                'paid_field' => 'caution_fee',
                'code' => 'CAUTION',
                'fallback' => 'Caution Fee',
            ],
        ];

        $codes = collect($map)
            ->filter(fn($m) => (bool) ($enrollment->{$m['include_field']} ?? false))
            ->pluck('code')
            ->values()
            ->all();

        if (empty($codes)) return [];

        $items = AdminFeeItem::query()
            ->where('is_active', true)
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        $rows = [];

        foreach ($map as $m) {
            if (!(bool) ($enrollment->{$m['include_field']} ?? false)) continue;

            $item = $items[$m['code']] ?? null;

            // Adjust if your column is 'amount' instead of 'default_amount'
            $expected = (float) ($item->default_amount ?? 0);
            $paid = (float) ($student->{$m['paid_field']} ?? 0);
            $balance = max(0, $expected - $paid);

            // For statement charges, show only what is actually outstanding
            if ($balance <= 0) continue;

            $rows[] = [
                'date' => $chargeDate->toDateString(),
                'type' => 'charge',
                'amount' => $balance,
                'description' => $item->name ?? $m['fallback'],
                'meta' => [
                    'kind' => 'admin_fee',
                    'code' => $m['code'],
                    'expected' => $expected,
                    'paid_so_far' => $paid,
                ],
            ];
        }

        return $rows;
    }

    /**
     * Fetch payment entries up to given date (source of truth).
     *
     * Assumptions (adjust if needed):
     * - payments table has: amount, created_at, maybe paid_at, maybe description
     */
    private static function makePaymentEntriesUpToEnd(int $enrollmentId, Carbon $end): array
    {
        $payments = Payment::query()
            ->where('enrollment_id', $enrollmentId)
            ->where(function ($q) use ($end) {
                // Use paid_at if it exists, otherwise created_at
                $q->whereDate('paid_at', '<=', $end->toDateString())
                  ->orWhere(function ($q2) use ($end) {
                      $q2->whereNull('paid_at')->whereDate('created_at', '<=', $end->toDateString());
                  });
            })
            ->orderByRaw('COALESCE(paid_at, created_at) ASC')
            ->get();

        return $payments->map(function ($p) {
            $date = $p->paid_at ?? $p->created_at;

            return [
                'date' => Carbon::parse($date)->toDateString(),
                'type' => 'payment',
                'amount' => (float) ($p->amount ?? 0),
                'description' => $p->description ?? 'Payment',
                'meta' => [
                    'payment_id' => $p->id,
                    'reference' => $p->reference ?? null,
                    'method' => $p->method ?? null,
                ],
            ];
        })->all();
    }

    /**
     * Running balance before trimester start:
     * balance = sum(charges) - sum(payments) for entries with date < start
     */
    private static function computeOpeningBalance($entriesAsc, Carbon $start): float
    {
        $running = 0.0;

        foreach ($entriesAsc as $r) {
            $d = Carbon::parse($r['date'])->startOfDay();
            if ($d->gte($start)) break;

            $amount = (float) ($r['amount'] ?? 0);
            if (($r['type'] ?? '') === 'charge') {
                $running += $amount;
            } elseif (($r['type'] ?? '') === 'payment') {
                $running -= $amount;
            }
        }

        return round($running, 2);
    }
}
