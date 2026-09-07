<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Trimester;
use App\Services\AcademicCalendarService;
use App\Services\EnrollmentProgressionService;
use App\Services\FeeGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTrimesterTransitions extends Command
{
    protected $signature = 'finance:process-trimester-transitions
        {--date= : Process transitions as of this date YYYY-MM-DD}
        {--dry-run : Preview only}';

    protected $description = 'Close completed progressions, activate current trimester progressions, and generate charges only when progressions are created/activated';

    protected array $billableEnrollmentStatuses = [
        'active',
        'course_completed',
        'pending_graduation',
        'graduated',
    ];

    // How many days before a trimester ends the system proactively creates
    // the next one, so there's a real trimester to roll into ahead of time
    // instead of only ever getting one reactively on the day it's needed.
    protected int $nextTrimesterLeadDays = 14;

    public function handle(): int
    {
        $processDate = $this->option('date')
            ? now()->parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: no records will be changed.');
        }

        try {
            DB::transaction(function () use ($processDate) {
                $this->syncTrimesterStatuses($processDate);

                $activeTrimester = $this->getActiveTrimester($processDate);

                $this->ensureNextTrimesterExists($activeTrimester, $processDate);

                $this->completeEndedProgressions($processDate);

                $this->ensureActiveProgressionsForTrimester($activeTrimester, $processDate);
            });

            $this->info('Trimester transitions processed successfully.');

            return self::SUCCESS;
        } catch (\Throwable $th) {
            Log::error('Trimester transition processing failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    protected function syncTrimesterStatuses($processDate): void
    {
        if ($this->option('dry-run')) {
            $this->line('Would sync trimester statuses.');
            return;
        }

        Trimester::query()
            ->whereDate('end_date', '<', $processDate)
            ->where('status', '!=', 'closed')
            ->update(['status' => 'closed']);

        Trimester::query()
            ->whereDate('start_date', '<=', $processDate)
            ->whereDate('end_date', '>=', $processDate)
            ->update(['status' => 'active']);

        Trimester::query()
            ->whereDate('start_date', '>', $processDate)
            ->where('status', '!=', 'upcoming')
            ->update(['status' => 'upcoming']);
    }

    protected function getActiveTrimester($processDate): Trimester
    {
        $activeTrimester = Trimester::query()
            ->whereDate('start_date', '<=', $processDate)
            ->whereDate('end_date', '>=', $processDate)
            ->first();

        if ($activeTrimester) {
            return $activeTrimester;
        }

        if ($this->option('dry-run')) {
            throw new \RuntimeException('No active trimester found for dry-run date.');
        }

        return app(AcademicCalendarService::class)
            ->getOrCreateTrimesterForDate($processDate);
    }

    protected function ensureNextTrimesterExists(Trimester $activeTrimester, $processDate): void
    {
        if ($this->option('dry-run')) {
            $daysToEnd = $processDate->diffInDays($activeTrimester->end_date, false);
            if ($daysToEnd <= $this->nextTrimesterLeadDays) {
                $this->line("Would ensure the trimester following {$activeTrimester->name} exists (within {$this->nextTrimesterLeadDays}-day lead time).");
            }
            return;
        }

        $created = app(AcademicCalendarService::class)
            ->ensureNextTrimesterWithinLeadTime($activeTrimester, $this->nextTrimesterLeadDays, $processDate);

        if ($created) {
            $this->info("Created upcoming trimester {$created->name} ({$created->start_date->toDateString()} - {$created->end_date->toDateString()}).");
        }
    }

    protected function completeEndedProgressions($processDate): void
    {
        // Standard (trimester-calendar) courses: their real end date IS
        // their linked trimester's end_date.
        $standardCandidates = EnrollmentProgression::query()
            ->where('status', 'active')
            ->whereHas('enrollment.course', fn($q) => $q->where('allows_continuous_intake', false))
            ->whereHas('trimester', function ($q) use ($processDate) {
                $q->whereDate('end_date', '<', $processDate);
            })
            ->with('trimester')
            ->get();

        // Continuous-intake courses (German levels, Barista, ICT
        // Certificate, ...) don't follow their linked trimester's calendar
        // at all — their real end date is started_at + the course's own
        // duration (EnrollmentProgression::computedDateRange()). A German
        // progression attached to a trimester that already closed is often
        // still genuinely running for weeks afterward, so each one has to
        // be checked individually rather than via a single date comparison.
        $continuousCandidates = EnrollmentProgression::query()
            ->where('status', 'active')
            ->whereHas('enrollment.course', fn($q) => $q->where('allows_continuous_intake', true))
            ->with(['enrollment.course', 'trimester'])
            ->get()
            ->filter(fn(EnrollmentProgression $p) => $processDate->gt($p->computedEndDate()));

        if ($this->option('dry-run')) {
            $this->line("Would complete {$standardCandidates->count()} ended active standard progression(s).");
            $this->line("Would complete {$continuousCandidates->count()} ended active continuous-intake progression(s).");
            return;
        }

        // completed_at is always stamped with the progression's real end
        // date — never $processDate. StudentLedgerService/progressionDates()
        // prefers completed_at over the trimester's own end_date once it's
        // set, so if this cron is ever delayed (e.g. the schedule:run cron
        // missing for a stretch) that would otherwise backdate every
        // affected statement's shown period to "whenever the cron finally
        // ran" instead of when the trimester/course genuinely ended.
        foreach ($standardCandidates as $progression) {
            $progression->update([
                'status' => 'completed',
                'completed_at' => $progression->trimester?->end_date?->toDateString() ?? $processDate->toDateString(),
            ]);
        }

        foreach ($continuousCandidates as $progression) {
            $progression->update([
                'status' => 'completed',
                'completed_at' => $progression->computedEndDate()->toDateString(),
            ]);

            $this->completeEnrollmentIfContinuousIntakeFinished($progression);
        }
    }

    /**
     * A continuous-intake enrollment (German level, Barista, ICT
     * Certificate, ...) has no "next trimester" to roll into once its last
     * progression finishes — unlike a multi-trimester course, there's no
     * ambiguity about whether the enrollment itself is done. Left alone,
     * Enrollment.status stays 'active' forever (nothing else ever
     * transitions it), so the student view's sidebar badge disagrees with
     * the progression's own "Completed" status. Standard multi-trimester
     * courses are deliberately left untouched here — MarkCourseCompletedAction
     * staying a manual, admin-driven step for those is the existing,
     * intentional design.
     */
    protected function completeEnrollmentIfContinuousIntakeFinished(EnrollmentProgression $progression): void
    {
        $enrollment = $progression->enrollment;

        if (!$enrollment || $enrollment->status !== 'active') {
            return;
        }

        if ((int) $progression->trimester_sequence < (int) $enrollment->course->number_of_trimesters) {
            return;
        }

        app(\App\Services\EnrollmentStatusService::class)->markCourseCompleted($enrollment);
    }

    protected function ensureActiveProgressionsForTrimester(Trimester $activeTrimester, $processDate): void
    {
        Enrollment::query()
            ->with(['course', 'progressions.trimester'])
            ->whereIn('status', $this->billableEnrollmentStatuses)
            ->whereNotNull('assigned_start_trimester_id')
            ->whereHas('course')
            ->chunkById(100, function ($enrollments) use ($activeTrimester, $processDate) {
                foreach ($enrollments as $enrollment) {
                    $this->processEnrollment($enrollment, $activeTrimester, $processDate);
                }
            });
    }

    protected function processEnrollment(Enrollment $enrollment, Trimester $activeTrimester, $processDate): void
    {
        if (!$enrollment->course) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Skip if enrollment has not reached its assigned start trimester
        |--------------------------------------------------------------------------
        */

        $assignedStart = $enrollment->assignedStartTrimester;

        if (
            $assignedStart &&
            $assignedStart->start_date &&
            $processDate->lt($assignedStart->start_date)
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | If active progression already exists for this trimester, only ensure fees
        |--------------------------------------------------------------------------
        */

        $existingForTrimester = EnrollmentProgression::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('trimester_id', $activeTrimester->id)
            ->first();

        if ($existingForTrimester) {
            if (!$this->option('dry-run')) {
                $existingForTrimester->update([
                    'status' => 'active',
                    'started_at' => $existingForTrimester->started_at
                        ?? $activeTrimester->start_date,
                ]);

                app(FeeGenerationService::class)
                    ->generateChargesForProgression($existingForTrimester);
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Decide whether next progression should be created now
        |--------------------------------------------------------------------------
        */

        $lastProgression = EnrollmentProgression::query()
            ->where('enrollment_id', $enrollment->id)
            ->orderByDesc('trimester_sequence')
            ->first();

        if (!$lastProgression) {
            $this->createFirstProgressionIfDue($enrollment, $activeTrimester);
            return;
        }

        if ($lastProgression->status !== 'completed') {
            return;
        }

        $nextSequence = (int) $lastProgression->trimester_sequence + 1;

        if ($nextSequence > (int) $enrollment->course->number_of_trimesters) {
            return;
        }

        // Never roll a student into a new trimester once they're
        // course_completed/pending_graduation/graduated — MarkCourseCompletedAction
        // doesn't verify all trimesters are actually finished before setting
        // that status, so number_of_trimesters alone isn't a reliable guard.
        if ($enrollment->status !== 'active') {
            return;
        }

        if ($this->option('dry-run')) {
            $this->line("Would create T{$nextSequence} progression for enrollment {$enrollment->id}.");
            return;
        }

        $progression = EnrollmentProgression::create([
            'student_id' => $enrollment->student_id,
            'enrollment_id' => $enrollment->id,
            'trimester_id' => $activeTrimester->id,
            'trimester_sequence' => $nextSequence,
            'status' => 'active',
            'started_at' => $activeTrimester->start_date,
        ]);

        app(FeeGenerationService::class)
            ->generateChargesForProgression($progression);
    }

    protected function createFirstProgressionIfDue(Enrollment $enrollment, Trimester $activeTrimester): void
    {
        if ((int) $enrollment->assigned_start_trimester_id !== (int) $activeTrimester->id) {
            return;
        }

        if ($this->option('dry-run')) {
            $this->line("Would create first progression for enrollment {$enrollment->id}.");
            return;
        }

        $progression = app(EnrollmentProgressionService::class)
            ->createFirstProgression($enrollment);

        app(FeeGenerationService::class)
            ->generateStudentOnceFees($enrollment);

        app(FeeGenerationService::class)
            ->generateChargesForProgression($progression);
    }
}
