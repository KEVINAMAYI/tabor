<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Console\Command;

/**
 * Read-only report. Surfaces payments broken by the MpesaApi::c2bConfirmation()
 * bypass bug (fixed alongside this command) — a payment with zero
 * PaymentAllocation rows and unallocated_balance still equal to its full
 * amount, despite the student having real outstanding fee items, because it
 * was created via a raw Payment::create() that skipped allocation entirely.
 *
 * Never writes anything. Use finance:allocate-orphaned-payments to actually
 * fix the "auto-fixable" rows this reports, after reviewing them.
 */
class AuditUnallocatedPayments extends Command
{
    protected $signature = 'finance:audit-unallocated-payments';

    protected $description = 'Report payments with zero allocations that likely bypassed PaymentPostingService (read-only)';

    public function handle(): int
    {
        $candidates = Payment::query()
            ->where('unallocated_balance', '>', 0)
            ->withCount('allocations')
            ->having('allocations_count', 0)
            ->with('student')
            ->orderBy('payment_date')
            ->get();

        $autoFixable = $candidates->whereNotNull('student_id');
        $needsManualMatch = $candidates->whereNull('student_id');

        $this->info("Payments with unallocated_balance > 0 and zero allocations: {$candidates->count()}");
        $this->newLine();

        if ($autoFixable->isNotEmpty()) {
            $this->line('<fg=green>Auto-fixable (student_id set — finance:allocate-orphaned-payments can allocate these):</>');
            $this->table(
                ['Payment #', 'Student', 'Amount', 'Reference', 'Date'],
                $autoFixable->map(fn (Payment $p) => [
                    $p->id,
                    $p->student->name ?? "student #{$p->student_id}",
                    number_format($p->amount, 2),
                    $p->reference ?? $p->transaction_id ?? '—',
                    optional($p->payment_date)->toDateString(),
                ])
            );
            $this->line('Total: KES ' . number_format($autoFixable->sum('amount'), 2));
            $this->newLine();
        }

        if ($needsManualMatch->isNotEmpty()) {
            $this->line('<fg=yellow>Needs manual review (no student_id — cannot be safely auto-matched):</>');
            $this->table(
                ['Payment #', 'Payer', 'Phone', 'Amount', 'Reference', 'Date'],
                $needsManualMatch->map(fn (Payment $p) => [
                    $p->id,
                    $p->payer ?? '—',
                    $p->phone ?? '—',
                    number_format($p->amount, 2),
                    $p->reference ?? $p->transaction_id ?? '—',
                    optional($p->payment_date)->toDateString(),
                ])
            );
            $this->line('Total: KES ' . number_format($needsManualMatch->sum('amount'), 2));
            $this->newLine();
        }

        $orphanedAllocations = PaymentAllocation::query()
            ->whereDoesntHave('studentFeeItem')
            ->with('payment.student')
            ->get();

        if ($orphanedAllocations->isNotEmpty()) {
            $this->line('<fg=red>Orphaned allocations (point at a deleted fee item — needs manual review, never auto-fixed):</>');
            $this->table(
                ['Allocation #', 'Payment #', 'Student', 'Amount', 'Missing fee item id'],
                $orphanedAllocations->map(fn (PaymentAllocation $a) => [
                    $a->id,
                    $a->payment_id,
                    $a->payment?->student?->name ?? "payment #{$a->payment_id}",
                    number_format($a->amount_allocated, 2),
                    $a->student_fee_item_id,
                ])
            );
        }

        if ($candidates->isEmpty() && $orphanedAllocations->isEmpty()) {
            $this->info('No issues found.');
        }

        return self::SUCCESS;
    }
}
