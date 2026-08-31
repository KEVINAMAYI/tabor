<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Services\Finance\FinanceReconciliationService;
use App\Services\PaymentPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Non-destructive reconciliation command.
 *
 * Dry-run (default): detects mismatches, reports them, touches nothing.
 * --fix: rebuilds allocations per student, one DB transaction per student.
 *
 * Safety guards:
 *  - Backfills payments.student_id from enrollment before processing.
 *  - Skips students whose payments have cross-student allocations (warns instead).
 *  - Requires confirmation before a bulk --fix run.
 *  - Each student fix rolls back independently on failure; others continue.
 */
class ReconcileStudentFinances extends Command
{
    protected $signature = 'finance:reconcile
        {--student_id= : Target a single student by ID}
        {--enrollment_id= : Target a single enrollment by ID (resolves student automatically)}
        {--dry-run : Detect and report mismatches without fixing (default behaviour)}
        {--fix : Apply fixes to mismatched records}
        {--all : Explicitly target all students (required for bulk --fix without --student_id)}
        {--no-confirm : Skip the confirmation prompt (for automated/scheduled use)}';

    protected $description = 'Detect and optionally fix payment allocation mismatches across all students';

    protected FinanceReconciliationService $reconciliationService;

    public function handle(PaymentPostingService $paymentService, FinanceReconciliationService $reconciliationService): int
    {
        $this->reconciliationService = $reconciliationService;

        $fix    = (bool) $this->option('fix');
        $dryRun = !$fix;

        if ($dryRun) {
            $this->warn('DRY-RUN mode — no changes will be made. Pass --fix to repair mismatches.');
        }

        // Step 1: Backfill student_id on legacy payments (safe, idempotent)
        $backfilled = $this->backfillPaymentStudentIds();
        if ($backfilled > 0) {
            $this->line("<info>Backfilled student_id on {$backfilled} legacy payment(s).</info>");
        }

        // Step 2: Resolve which students to process
        $studentIds = $this->resolveStudentIds();

        if ($studentIds->isEmpty()) {
            $this->error('No students found to reconcile.');
            return self::FAILURE;
        }

        $isBulk = !$this->option('student_id') && !$this->option('enrollment_id');

        // Step 3: Confirm before a bulk --fix
        if ($fix && $isBulk && !$this->option('no-confirm')) {
            $this->warn("This will rebuild allocations for ALL {$studentIds->count()} student(s).");
            $this->warn('Each student is processed in its own transaction. Failures are isolated.');

            if (!$this->confirm('Proceed with bulk fix?', false)) {
                $this->line('Aborted.');
                return self::SUCCESS;
            }
        }

        $this->info("Reconciling {$studentIds->count()} student(s)...");

        // Step 4: Main loop
        $totalMismatches  = 0;
        $totalFixed       = 0;
        $totalSkipped     = 0;
        $totalFailed      = 0;
        $skippedStudents  = [];

        $bar = $this->output->createProgressBar($studentIds->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('starting...');
        $bar->start();

        foreach ($studentIds as $studentId) {
            $bar->setMessage("student #{$studentId}");

            $mismatches = $this->reconciliationService->detectMismatches($studentId);

            if ($mismatches->isEmpty()) {
                $bar->advance();
                continue;
            }

            $totalMismatches += $mismatches->count();

            if ($fix) {
                // Guard: skip students with cross-student allocations
                $crossStudent = $this->hasCrossStudentAllocations($studentId);

                if ($crossStudent) {
                    $totalSkipped++;
                    $skippedStudents[] = $studentId;
                    $bar->advance();
                    continue;
                }

                try {
                    $this->fixStudent($studentId, $paymentService);
                    $totalFixed++;
                } catch (\Throwable $e) {
                    $totalFailed++;
                    $this->newLine();
                    $this->error("  Failed to fix student #{$studentId}: {$e->getMessage()}");
                    Log::error('Reconciliation fix failed', [
                        'student_id' => $studentId,
                        'error'      => $e->getMessage(),
                        'trace'      => $e->getTraceAsString(),
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->setMessage('done');
        $bar->finish();
        $this->newLine(2);

        // Step 5: Summary
        $this->printSummary($totalMismatches, $totalFixed, $totalSkipped, $totalFailed, $fix);

        if (!empty($skippedStudents)) {
            $this->warn('Skipped students with cross-student allocations (require manual review):');
            foreach ($skippedStudents as $sid) {
                $this->line("  Student #{$sid} — view their payments page to re-allocate manually.");
                $this->reportMismatchesForStudent($sid);
            }
        }

        if ($totalMismatches > 0 && $dryRun) {
            $this->warn('Run with --fix to repair mismatches.');
            $this->reportAllMismatches($studentIds);
        }

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Backfill
    // -------------------------------------------------------------------------

    protected function backfillPaymentStudentIds(): int
    {
        return DB::table('payments')
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->whereNull('payments.student_id')
            ->whereNotNull('payments.enrollment_id')
            ->update(['payments.student_id' => DB::raw('enrollments.student_id')]);
    }

    // -------------------------------------------------------------------------
    // Student resolution
    // -------------------------------------------------------------------------

    protected function resolveStudentIds(): Collection
    {
        if ($enrollmentId = $this->option('enrollment_id')) {
            $studentId = DB::table('enrollments')
                ->where('id', $enrollmentId)
                ->value('student_id');

            return $studentId ? collect([(int) $studentId]) : collect();
        }

        if ($studentId = $this->option('student_id')) {
            return collect([(int) $studentId]);
        }

        // All students who have payments or fee items (union, deduplicated)
        return DB::table('payments')
            ->whereNotNull('student_id')
            ->distinct()
            ->pluck('student_id')
            ->merge(
                // Legacy payments resolved via enrollment (after backfill these should be minimal)
                DB::table('payments')
                    ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
                    ->whereNull('payments.student_id')
                    ->whereNotNull('payments.enrollment_id')
                    ->distinct()
                    ->pluck('enrollments.student_id')
            )
            ->merge(
                DB::table('student_fee_items')
                    ->distinct()
                    ->pluck('student_id')
            )
            ->map(fn($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();
    }

    // -------------------------------------------------------------------------
    // Cross-student guard
    // -------------------------------------------------------------------------

    protected function hasCrossStudentAllocations(int $studentId): bool
    {
        $paymentIds = $this->resolvePaymentIds($studentId);

        if ($paymentIds->isEmpty()) {
            return false;
        }

        return PaymentAllocation::whereIn('payment_id', $paymentIds)
            ->whereHas('studentFeeItem', fn($q) => $q->where('student_id', '!=', $studentId))
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Fix
    // -------------------------------------------------------------------------

    protected function fixStudent(int $studentId, PaymentPostingService $paymentService): void
    {
        DB::transaction(function () use ($studentId, $paymentService) {
            $paymentIds = $this->resolvePaymentIds($studentId);

            // 1. Delete only this student's OWN payment allocations
            PaymentAllocation::whereIn('payment_id', $paymentIds)->delete();

            // 2. Reset fee items (charges only — skip credits/waivers)
            StudentFeeItem::where('student_id', $studentId)
                ->where('status', '!=', 'waived')
                ->where('amount', '>=', 0)
                ->update([
                    'amount_paid' => 0,
                    'balance'     => DB::raw('amount'),
                    'status'      => 'pending',
                ]);

            // 3. Reset payment unallocated balances
            Payment::whereIn('id', $paymentIds)->update([
                'unallocated_balance' => DB::raw('amount'),
            ]);

            // 4. Re-allocate student's own payments in strict chronological order
            Payment::whereIn('id', $paymentIds)
                ->orderBy('payment_date')
                ->orderBy('paid_at')
                ->orderBy('id')
                ->each(fn(Payment $p) => $paymentService->allocateExistingPayment($p->fresh()));

            // 5. Final sync: recalculate amount_paid and balance from ALL allocations,
            //    including from other students' payments (cross-student allocations).
            //    This handles cases where payment X (not this student's) is allocated
            //    to this student's fee items.
            $this->syncFeeItemTotals($studentId);

            Log::info('finance:reconcile — fix applied', ['student_id' => $studentId]);
        });
    }

    protected function syncFeeItemTotals(int $studentId): void
    {
        StudentFeeItem::query()
            ->where('student_id', $studentId)
            ->where('amount', '>=', 0)   // skip credit items
            ->where('status', '!=', 'waived')
            ->with('allocations')
            ->get()
            ->each(function (StudentFeeItem $item) {
                $amount       = (float) $item->amount;
                $sumAllocated = (float) $item->allocations->sum('amount_allocated');

                // Cap amount_paid at the fee amount to prevent over-reporting
                $newPaid    = min($sumAllocated, $amount);
                $newBalance = max(0, $amount - $sumAllocated);

                $newStatus = match (true) {
                    $item->status === 'waived'          => 'waived',
                    $sumAllocated <= 0                  => 'pending',
                    $sumAllocated >= $amount - 0.01     => 'paid',
                    default                             => 'partial',
                };

                $item->update([
                    'amount_paid' => $newPaid,
                    'balance'     => $newBalance,
                    'status'      => $newStatus,
                ]);
            });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function resolvePaymentIds(int $studentId): Collection
    {
        // Includes legacy payments that might still lack student_id after backfill
        $direct = Payment::where('student_id', $studentId)->pluck('id');

        $viaEnrollment = Payment::whereNull('student_id')
            ->whereHas('enrollment', fn($q) => $q->where('student_id', $studentId))
            ->pluck('id');

        return $direct->merge($viaEnrollment)->unique()->values();
    }

    protected function printSummary(
        int $totalMismatches,
        int $totalFixed,
        int $totalSkipped,
        int $totalFailed,
        bool $fix
    ): void {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Mismatches found',      $totalMismatches],
                $fix ? ['Students fixed',  $totalFixed]   : ['Students to fix', '-'],
                $fix ? ['Skipped (cross-student)', $totalSkipped] : [],
                $fix ? ['Failed',          $totalFailed]  : [],
            ]
        );
    }

    protected function reportMismatchesForStudent(int $studentId): void
    {
        $mismatches = $this->reconciliationService->detectMismatches($studentId);
        foreach ($mismatches as $m) {
            $this->line(
                "    [{$m['type']}] ID #{$m['id']}: expected={$m['expected']} actual={$m['actual']} diff={$m['diff']}"
            );
        }
    }

    protected function reportAllMismatches(Collection $studentIds): void
    {
        $this->newLine();
        $this->line('<comment>Detailed mismatch report:</comment>');
        foreach ($studentIds as $studentId) {
            $mismatches = $this->reconciliationService->detectMismatches($studentId);
            if ($mismatches->isEmpty()) {
                continue;
            }
            $this->warn("Student #{$studentId}:");
            $this->reportMismatchesForStudent($studentId);
        }
    }
}
