<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use Carbon\Carbon;

class StatementController extends Controller
{
    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['course', 'student', 'trimesters']);

        // 1) Current trimester: in-progress else latest
        $current = $enrollment->trimesters()
            ->where('status', 'in-progress')
            ->orderByDesc('trimester_number')
            ->first()
            ?? $enrollment->trimesters()->orderByDesc('trimester_number')->first();

        if (!$current) {
            abort(404, 'No trimester found for this enrollment.');
        }

        $currentNumber = (int) $current->trimester_number;

        // 2) Trimesters oldest -> newest (FIFO debt buckets)
        $trimestersAsc = $enrollment->trimesters()
            ->orderBy('trimester_number', 'asc')
            ->get();

        // 3) Payments oldest -> newest (FIFO stream)
        $paymentsAsc = Payment::where('enrollment_id', $enrollment->id)
            ->orderByRaw("COALESCE(paid_at, created_at) asc")
            ->get();

        // Keep original payment records for description/method if you need them later
        // Map for quick lookup by id
        $paymentById = $paymentsAsc->keyBy('id');

        // 4) Build debts as plain array so we can mutate
        $debts = $trimestersAsc->map(function ($t) {
            $expected = (float) $t->fee_amount;

            return [
                'trimester_id' => $t->id,
                'trimester_number' => (int) $t->trimester_number,
                'start_date' => $t->start_date,
                'expected' => $expected,
                'remaining' => $expected,
                'paid_allocated' => 0.0,
            ];
        })->values()->toArray();

        // 5) FIFO allocate payments across debts
        // Track allocations (so we can show only allocations that hit the current trimester)
        $allocations = []; // each: payment_id, payment_date, trimester_number, amount

        foreach ($paymentsAsc as $p) {
            $payAmount = (float) $p->amount;
            if ($payAmount <= 0) continue;

            for ($i = 0; $i < count($debts); $i++) {
                if ($payAmount <= 0) break;
                if ($debts[$i]['remaining'] <= 0) continue;

                $use = min($payAmount, $debts[$i]['remaining']);

                $debts[$i]['remaining'] -= $use;
                $debts[$i]['paid_allocated'] += $use;
                $payAmount -= $use;

                $allocations[] = [
                    'payment_id' => $p->id,
                    'payment_date' => $p->paid_at ?? $p->created_at,
                    'trimester_number' => $debts[$i]['trimester_number'],
                    'amount' => $use,
                ];
            }
        }

        $debtsCol = collect($debts);

        // 6) Opening balance = what is still owed from trimesters BEFORE current (FIFO remaining)
        // Under FIFO this is the ONLY correct "b/f" value.
        $openingBalance = (float) $debtsCol
            ->where('trimester_number', '<', $currentNumber)
            ->sum('remaining');

        // 7) Current trimester debt snapshot
        $currentDebt = $debtsCol->firstWhere('trimester_number', $currentNumber);

        $expectedCurrent = (float) ($currentDebt['expected'] ?? 0.0);
        $currentRemainingAfterAllPayments = (float) ($currentDebt['remaining'] ?? 0.0);

        // 8) Credit after paying ALL trimesters (this is the only real negative balance in FIFO)
        $totalExpectedAll = (float) $debtsCol->sum('expected');
        $totalPaidAll     = (float) $paymentsAsc->sum('amount');
        $creditAfterAll   = max(0.0, $totalPaidAll - $totalExpectedAll);

        // 9) Build LEDGER FOR CURRENT TRIMESTER ONLY
        // - Opening balance row (b/f)
        // - Current trimester tuition fee charge
        // - Payments that FIFO allocated to CURRENT trimester (not all payments)
        $ledger = collect();

        if ($openingBalance != 0.0) {
            $ledger->push([
                'date' => $current->start_date,
                'amount' => $openingBalance,
                'description' => 'Opening balance (B/F)',
                'type' => 'bf', // special type for display
            ]);
        }

        $ledger->push([
            'date' => $current->start_date,
            'amount' => $expectedCurrent,
            'description' => 'Tuition Fee - Trimester ' . $currentNumber,
            'type' => 'charge',
        ]);

        // Filter allocations that hit CURRENT trimester and show them as payments
        $allocToCurrent = collect($allocations)
            ->where('trimester_number', $currentNumber)
            ->sortBy(fn($a) => Carbon::parse($a['payment_date']))
            ->values();

        foreach ($allocToCurrent as $a) {
            $p = $paymentById->get($a['payment_id']);

            $ledger->push([
                'date' => $a['payment_date'],
                'amount' => (float) $a['amount'],
                'description' => $p?->payment_reason ?? 'Payment',
                'type' => 'payment',
            ]);
        }

        // Display newest first (like you want)
        $ledger = $ledger->sortByDesc(fn($r) => Carbon::parse($r['date']))->values();

        // 10) What should the "Balance owing" show for current trimester statement?
        // Total owing as at now = openingBalance (arrears from before) + current trimester remaining (after FIFO allocations)
        // BUT currentRemainingAfterAllPayments is only the remaining on the current trimester bucket,
        // it does not include openingBalance. Your "current statement" should show total outstanding:
        $totalOutstandingNow = $openingBalance + $currentRemainingAfterAllPayments;

        return view('exports.payments.statement', [
            'enrollment' => $enrollment,
            'course' => $enrollment->course,
            'trimester' => $current,
            'count' => $trimestersAsc->count(),

            // Only current ledger
            'ledger' => $ledger,

            // Summary numbers you’ll show in the blade
            'openingBalance' => $openingBalance,                 // arrears b/f (>= 0 in FIFO)
            'expectedCurrent' => $expectedCurrent,
            'currentRemaining' => $currentRemainingAfterAllPayments,
            'totalOutstandingNow' => $totalOutstandingNow,

            // optional: only if you want to display “credit” when everything is cleared
            'creditAfterAll' => $creditAfterAll,
        ]);
    }
}
