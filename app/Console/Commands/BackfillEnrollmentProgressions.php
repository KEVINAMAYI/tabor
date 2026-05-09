<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\FeeDefinition;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\StudentFeeItem;
use App\Services\CourseFeePlanSyncService;
use App\Services\EnrollmentProgressionService;
use App\Services\FeeGenerationService;
use App\Services\PaymentPostingService;
use App\Services\TrimesterAssignmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BackfillEnrollmentProgressions extends Command
{
    protected $signature = 'finance:backfill-progressions
        {--dry-run : Show counts only}
        {--yes : Run without confirmation}
        {--skip-allocations : Do not rebuild payment allocations}';

    protected $description = 'Backfill finance data: courses, enrollments, progressions, fee items, payments, and allocations';


    protected array $billableEnrollmentStatuses = [
        'active',
        'course_completed',
        'pending_graduation',
        'graduated',
    ];
    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: No records will be changed.');
            $this->dryRunSummary();

            return self::SUCCESS;
        }

        if (!$this->option('yes')) {
            if (!$this->confirm('This will update finance/enrollment data and may rebuild payment allocations. Continue?')) {
                $this->warn('Cancelled.');

                return self::SUCCESS;
            }
        }

        $this->info('Starting finance backfill...');

        try {
            $this->syncCourseBillingTemplates();
            $this->assignMissingEnrollmentTrimesters();
            $this->backfillProgressions();
            $this->backfillStudentOnceFees();
            $this->backfillProgressionCharges();
            $this->linkExistingFeeItemsToProgressions();

            // if (!$this->option('skip-allocations')) {
            $this->rebuildPaymentAllocations();
            // }

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
        $this->line('Enrollments: ' . Enrollment::count());

        $this->line(
            'Billable enrollments: ' .
            Enrollment::whereIn('status', $this->billableEnrollmentStatuses)->count()
        );

        $this->line(
            'Pending/non-billable enrollments: ' .
            Enrollment::whereNotIn('status', $this->billableEnrollmentStatuses)->count()
        );

        $this->line(
            'Billable enrollments missing assigned start trimester: ' .
            Enrollment::whereIn('status', $this->billableEnrollmentStatuses)
                ->whereNull('assigned_start_trimester_id')
                ->count()
        );
        $this->line('Existing progressions: ' . EnrollmentProgression::count());
        $this->line('Existing fee items: ' . StudentFeeItem::count());
        $this->line('Payments: ' . Payment::count());
        $this->line('Existing allocations: ' . PaymentAllocation::count());
        $this->line('Fee items missing progression: ' . StudentFeeItem::whereNull('enrollment_progression_id')->count());
    }

    protected function syncCourseBillingTemplates(): void
    {
        $this->info('Syncing missing course billing templates...');

        $processed = 0;

        Course::query()
            ->orderBy('id')
            ->chunkById(100, function ($courses) use (&$processed) {
                foreach ($courses as $course) {
                    app(CourseFeePlanSyncService::class)
                        ->syncDefaultsForCourse($course, overwriteExisting: false);

                    $this->ensureDefaultStudentOnceFeeSelection($course);

                    $processed++;
                }
            });

        $this->info("Course billing templates checked for {$processed} course(s).");
    }

    protected function ensureDefaultStudentOnceFeeSelection(Course $course): void
    {
        if (!empty($course->chargeable_student_once_fee_definition_ids)) {
            return;
        }

        $ids = FeeDefinition::query()
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->where('active', true)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();

        $course->forceFill([
            'chargeable_student_once_fee_definition_ids' => $ids,
        ])->save();
    }

    protected function assignMissingEnrollmentTrimesters(): void
    {
        $this->info('Assigning missing enrollment trimesters...');

        $processed = 0;

        Enrollment::query()
            ->whereIn('status', $this->billableEnrollmentStatuses)
            ->with('course')
            ->where(function ($query) {
                $query->whereNull('intake_trimester_id')
                    ->orWhereNull('assigned_start_trimester_id');
            })
            ->whereNotNull('course_id')
            ->whereNotNull('admission_date')
            ->orderBy('id')
            ->chunkById(100, function ($enrollments) use (&$processed) {
                foreach ($enrollments as $enrollment) {
                    if (!$enrollment->course) {
                        continue;
                    }

                    $assignment = app(TrimesterAssignmentService::class)
                        ->assign(
                            Carbon::parse($enrollment->admission_date),
                            $enrollment->course
                        );

                    $enrollment->update([
                        'intake_trimester_id' => $enrollment->intake_trimester_id
                            ?: $assignment['intake_trimester_id'],

                        'assigned_start_trimester_id' => $enrollment->assigned_start_trimester_id
                            ?: $assignment['assigned_start_trimester_id'],
                    ]);

                    $processed++;
                }
            });

        $this->info("Trimester assignment checked for {$processed} enrollment(s).");
    }

    protected function backfillProgressions(): void
    {
        $this->info('Backfilling enrollment progressions...');

        $processed = 0;

        Enrollment::query()
            ->whereIn('status', $this->billableEnrollmentStatuses)
            ->with(['course', 'assignedStartTrimester.academicYear'])
            ->whereNotNull('assigned_start_trimester_id')
            ->orderBy('id')
            ->chunkById(100, function ($enrollments) use (&$processed) {
                foreach ($enrollments as $enrollment) {
                    app(EnrollmentProgressionService::class)
                        ->generateForEnrollment($enrollment);

                    $processed++;
                }
            });

        $this->info("Progressions processed for {$processed} enrollment(s).");
    }

    protected function backfillStudentOnceFees(): void
    {
        $this->info('Generating missing student-once fees...');

        $processed = 0;

        Enrollment::query()
            ->whereIn('status', $this->billableEnrollmentStatuses)
            ->with(['student', 'course'])
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
            ->whereHas('enrollment', function ($q) {
                $q->whereIn('status', $this->billableEnrollmentStatuses);
            })
            ->orderBy('id')
            ->chunkById(100, function ($progressions) use (&$processed) {
                foreach ($progressions as $progression) {
                    app(FeeGenerationService::class)
                        ->generateChargesForProgression($progression);

                    $processed++;
                }
            });

        $this->info("Charges processed for {$processed} progression(s).");
    }

    protected function linkExistingFeeItemsToProgressions(): void
    {
        $this->info('Linking existing fee items to progressions...');

        $statuses = "'" . implode("','", $this->billableEnrollmentStatuses) . "'";

        DB::statement("
    UPDATE student_fee_items sfi
    JOIN enrollments e
      ON e.id = sfi.enrollment_id
     AND e.status IN ({$statuses})
    JOIN enrollment_progressions ep
      ON ep.enrollment_id = sfi.enrollment_id
     AND ep.trimester_id = sfi.trimester_id
    SET sfi.enrollment_progression_id = ep.id
    WHERE sfi.enrollment_id IS NOT NULL
      AND sfi.trimester_id IS NOT NULL
      AND sfi.enrollment_progression_id IS NULL
");

        DB::statement("
    UPDATE student_fee_items sfi
    JOIN enrollments e
      ON e.id = sfi.enrollment_id
     AND e.status IN ({$statuses})
    JOIN enrollment_progressions ep
      ON ep.enrollment_id = sfi.enrollment_id
     AND sfi.charge_date BETWEEN COALESCE(ep.started_at, ep.created_at)
                             AND COALESCE(ep.completed_at, ep.updated_at)
    SET sfi.enrollment_progression_id = ep.id,
        sfi.trimester_id = COALESCE(sfi.trimester_id, ep.trimester_id)
    WHERE sfi.enrollment_id IS NOT NULL
      AND sfi.enrollment_progression_id IS NULL
      AND sfi.charge_date IS NOT NULL
");

        $this->info('Existing fee items linked to progressions.');
    }

    protected function rebuildPaymentAllocations(): void
    {
        $this->warn('Rebuilding payment allocations...');

        DB::transaction(function () {
            PaymentAllocation::query()->delete();

            StudentFeeItem::query()->update([
                'amount_paid' => 0,
                'balance' => DB::raw('amount'),
                'status' => 'pending',
            ]);

            if (Schema::hasColumn('payments', 'unallocated_balance')) {
                Payment::query()->update([
                    'unallocated_balance' => DB::raw('amount'),
                ]);
            }
        });

        $processed = 0;

        Payment::query()
            ->whereNotNull('payment_date')
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

        DB::transaction(function () {
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

            if (Schema::hasColumn('payments', 'unallocated_balance')) {
                DB::statement("
                    UPDATE payments p
                    SET p.unallocated_balance = p.amount - COALESCE((
                        SELECT SUM(pa.amount_allocated)
                        FROM payment_allocations pa
                        WHERE pa.payment_id = p.id
                    ), 0)
                ");
            }
        });

        $this->info("Payments allocated: {$processed}.");
    }
}
