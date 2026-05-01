<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\StudentFeeItem;
use App\Services\EnrollmentProgressionService;
use App\Services\FeeGenerationService;
use App\Services\PaymentPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillEnrollmentProgressions extends Command
{
    protected $signature = 'finance:backfill-progressions {--dry-run}';

    protected $description = 'Backfill enrollment progressions, progression charges, and payment allocations';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN: No records will be changed.');
            $this->dryRunSummary();

            return self::SUCCESS;
        }

        $this->info('Starting finance backfill...');

        try {
            DB::transaction(function () {
                $this->backfillProgressions();
                $this->backfillStudentOnceFees();
                $this->backfillProgressionCharges();
                $this->linkExistingFeeItemsToProgressions();
                $this->rebuildPaymentAllocations();
            });

            $this->info('Finance backfill completed successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Finance backfill failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('Finance backfill failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    protected function dryRunSummary(): void
    {
        $this->line('Enrollments to process: ' . Enrollment::whereNotNull('assigned_start_trimester_id')->count());
        $this->line('Existing progressions: ' . EnrollmentProgression::count());
        $this->line('Existing fee items: ' . StudentFeeItem::count());
        $this->line('Payments to allocate: ' . Payment::count());
        $this->line('Existing allocations: ' . PaymentAllocation::count());
    }

    protected function backfillProgressions(): void
    {
        $this->info('Backfilling enrollment progressions...');

        $processed = 0;

        Enrollment::query()
            ->with(['course', 'assignedStartTrimester.academicYear'])
            ->whereNotNull('assigned_start_trimester_id')
            ->orderBy('id')
            ->chunkById(100, function ($enrollments) use (&$processed) {
                foreach ($enrollments as $enrollment) {
                    try {
                        app(EnrollmentProgressionService::class)
                            ->generateForEnrollment($enrollment);

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::error('Failed to backfill enrollment progression', [
                            'enrollment_id' => $enrollment->id,
                            'student_id' => $enrollment->student_id,
                            'message' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }
            });

        $this->info("Progressions processed for {$processed} enrollment(s).");
    }

    protected function backfillStudentOnceFees(): void
    {
        $this->info('Generating missing student-once fees...');

        $processed = 0;

        Enrollment::query()
            ->with('student')
            ->whereNotNull('admission_date')
            ->orderBy('admission_date')
            ->orderBy('id')
            ->chunkById(100, function ($enrollments) use (&$processed) {
                foreach ($enrollments as $enrollment) {
                    app(FeeGenerationService::class)
                        ->generateStudentOnceFees($enrollment);

                    $processed++;
                }
            });

        $this->info("Student-once fees checked for {$processed} enrollment(s).");
    }

    protected function backfillProgressionCharges(): void
    {
        $this->info('Generating missing charges for progressions...');

        $processed = 0;

        EnrollmentProgression::query()
            ->with(['enrollment.course', 'trimester', 'student'])
            ->orderBy('id')
            ->chunkById(100, function ($progressions) use (&$processed) {
                foreach ($progressions as $progression) {
                    try {
                        app(FeeGenerationService::class)
                            ->generateChargesForProgression($progression);

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::error('Failed to generate charges for progression', [
                            'progression_id' => $progression->id,
                            'enrollment_id' => $progression->enrollment_id,
                            'student_id' => $progression->student_id,
                            'message' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }
            });

        $this->info("Charges processed for {$processed} progression(s).");
    }

    protected function linkExistingFeeItemsToProgressions(): void
    {
        $this->info('Linking existing fee items to progressions...');

        DB::statement("
            UPDATE student_fee_items sfi
            JOIN enrollment_progressions ep
              ON ep.enrollment_id = sfi.enrollment_id
             AND ep.trimester_id = sfi.trimester_id
            SET sfi.enrollment_progression_id = ep.id
            WHERE sfi.enrollment_id IS NOT NULL
              AND sfi.trimester_id IS NOT NULL
              AND sfi.enrollment_progression_id IS NULL
        ");

        $this->info('Existing fee items linked to progressions.');
    }

    protected function rebuildPaymentAllocations(): void
    {
        $this->info('Rebuilding payment allocations...');

        PaymentAllocation::query()->delete();

        StudentFeeItem::query()->update([
            'amount_paid' => 0,
            'balance' => DB::raw('amount'),
            'status' => 'pending',
        ]);

        $processed = 0;

        Payment::query()
            ->orderBy('payment_date')
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (&$processed) {
                foreach ($payments as $payment) {
                    try {
                        app(PaymentPostingService::class)
                            ->allocateExistingPayment($payment);

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::error('Failed to allocate existing payment', [
                            'payment_id' => $payment->id,
                            'student_id' => $payment->student_id,
                            'enrollment_id' => $payment->enrollment_id,
                            'message' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }
            });

        StudentFeeItem::query()->update([
            'balance' => DB::raw('amount - amount_paid'),
            'status' => DB::raw("
                CASE
                    WHEN amount - amount_paid <= 0 THEN 'paid'
                    WHEN amount_paid > 0 THEN 'partial'
                    ELSE 'pending'
                END
            "),
        ]);

        $this->info("Payments allocated: {$processed}.");
    }
}
