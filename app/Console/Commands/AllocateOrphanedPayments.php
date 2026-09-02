<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Guarded fix for exactly the payments finance:audit-unallocated-payments
 * flags as "auto-fixable": student_id set, zero PaymentAllocation rows,
 * unallocated_balance still equal to the full amount — created via the
 * MpesaApi::c2bConfirmation() bypass bug (fixed alongside this command).
 *
 * Deliberately narrower than finance:rebuild-payment-allocations, which
 * resets and replays EVERY payment/fee-item in the system. This command only
 * ever touches payments matching the specific broken-by-bypass signature —
 * it never resets an existing allocation, since these payments have none.
 */
class AllocateOrphanedPayments extends Command
{
    protected $signature = 'finance:allocate-orphaned-payments
        {--fix : Actually allocate. Without this flag, only previews.}';

    protected $description = 'Allocate payments that bypassed PaymentPostingService (zero allocations, student_id set) — dry-run by default';

    public function handle(PaymentPostingService $paymentService): int
    {
        $payments = Payment::query()
            ->where('unallocated_balance', '>', 0)
            ->whereNotNull('student_id')
            ->withCount('allocations')
            ->having('allocations_count', 0)
            ->with('student')
            ->orderBy('payment_date')
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No auto-fixable payments found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Payment #', 'Student', 'Amount', 'Reference'],
            $payments->map(fn (Payment $p) => [
                $p->id,
                $p->student->name ?? "student #{$p->student_id}",
                number_format($p->amount, 2),
                $p->reference ?? $p->transaction_id ?? '—',
            ])
        );

        if (!$this->option('fix')) {
            $this->warn('DRY RUN — nothing was allocated. Re-run with --fix to apply.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Allocate {$payments->count()} payment(s) totalling KES " . number_format($payments->sum('amount'), 2) . '?', false)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($payments, $paymentService) {
            foreach ($payments as $payment) {
                $paymentService->allocateExistingPayment($payment->fresh());
            }
        });

        $this->info("Allocated {$payments->count()} payment(s).");

        return self::SUCCESS;
    }
}
