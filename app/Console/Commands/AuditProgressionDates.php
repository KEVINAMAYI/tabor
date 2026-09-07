<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Services\EnrollmentStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Read-only audit for EnrollmentProgression status/date accuracy, built
 * after finding two related rollover bugs (Sep 2026): completeEndedProgressions()
 * used to (a) complete continuous-intake courses (German levels, Barista,
 * ICT Certificate) based on their linked trimester's end_date instead of
 * their own started_at + duration, and (b) stamp completed_at with
 * whenever the cron happened to run instead of the progression's real end
 * date — both silently corrupting the statement period shown for affected
 * students. Both are now fixed going forward; this finds EXISTING damage.
 *
 * Flags three categories, all against "today" (or --as-of):
 *   - completed_too_early: status=completed but the real computed end date
 *     is still in the future — should currently be 'active'.
 *   - overdue_for_completion: status=active but the real computed end date
 *     has already passed — informational only, self-heals on the next
 *     finance:process-trimester-transitions run.
 *   - completed_at_inaccurate: status=completed and the real end date has
 *     passed too, but the stored completed_at doesn't match it — cosmetic,
 *     affects only the exact date shown on the statement, not the
 *     amounts.
 */
class AuditProgressionDates extends Command
{
    protected $signature = 'finance:audit-progression-dates
        {--as-of= : Audit as of this date YYYY-MM-DD, defaults to today}
        {--fix : Correct "completed too early" (revert to active) and "completed_at inaccurate" (correct the stored date) rows. Without this flag, only previews.}';

    protected $description = 'Audit (and optionally fix) EnrollmentProgression status/completed_at accuracy against each progression\'s real computed end date';

    public function handle(): int
    {
        $asOf = $this->option('as-of') ? Carbon::parse($this->option('as-of'))->startOfDay() : now()->startOfDay();

        $progressions = EnrollmentProgression::query()
            ->whereIn('status', ['active', 'completed'])
            ->with(['student', 'enrollment.course', 'trimester'])
            ->get();

        $completedTooEarly = collect();
        $overdueForCompletion = collect();
        $completedAtInaccurate = collect();

        foreach ($progressions as $progression) {
            $realEnd = $progression->computedEndDate();

            if ($progression->status === 'completed' && $asOf->lt($realEnd)) {
                $completedTooEarly->push([$progression, $realEnd]);
                continue;
            }

            if ($progression->status === 'active' && $asOf->gt($realEnd)) {
                $overdueForCompletion->push([$progression, $realEnd]);
                continue;
            }

            if ($progression->status === 'completed' && $asOf->gte($realEnd)) {
                $storedCompletedAt = $progression->completed_at?->toDateString();
                if ($storedCompletedAt !== $realEnd->toDateString()) {
                    $completedAtInaccurate->push([$progression, $realEnd]);
                }
            }
        }

        // A continuous-intake enrollment (German level, Barista, ICT
        // Certificate, ...) has no "next trimester" to roll into once its
        // last progression finishes, so nothing else ever transitions
        // Enrollment.status away from 'active' — it stays stuck showing
        // "Active" in the UI forever even once the course is genuinely
        // done. Scoped to continuous-intake only: standard multi-trimester
        // courses deliberately keep MarkCourseCompletedAction as a manual,
        // admin-driven step.
        //
        // Guarded against completedTooEarly: a progression stored as
        // 'completed' but about to be reverted to 'active' above must
        // never also drive its enrollment to 'course_completed' here —
        // that would be a direct contradiction applied in the same run.
        $completedTooEarlyIds = $completedTooEarly->map(fn($pair) => $pair[0]->id);

        $staleEnrollmentStatus = Enrollment::query()
            ->where('status', 'active')
            ->whereHas('course', fn($q) => $q->where('allows_continuous_intake', true))
            ->with(['student', 'course', 'progressions'])
            ->get()
            ->filter(function (Enrollment $enrollment) use ($completedTooEarlyIds) {
                $lastProgression = $enrollment->progressions->sortByDesc('trimester_sequence')->first();
                return $lastProgression
                    && $lastProgression->status === 'completed'
                    && !$completedTooEarlyIds->contains($lastProgression->id)
                    && (int) $lastProgression->trimester_sequence >= (int) $enrollment->course->number_of_trimesters;
            });

        $this->report('COMPLETED TOO EARLY — should currently be active', $completedTooEarly);
        $this->report('OVERDUE FOR COMPLETION — still active, real end date has passed (self-heals on next rollover run)', $overdueForCompletion);
        $this->report('COMPLETED_AT INACCURATE — status is right, stored date is wrong (cosmetic)', $completedAtInaccurate);

        $this->components->info("STALE ENROLLMENT STATUS — continuous-intake, last progression completed, but Enrollment.status still 'active' ({$staleEnrollmentStatus->count()})");
        if ($staleEnrollmentStatus->isEmpty()) {
            $this->info('None found.');
            $this->newLine();
        } else {
            $this->table(['Enrollment #', 'Student', 'Course'], $staleEnrollmentStatus->map(fn(Enrollment $e) => [
                $e->id,
                trim(($e->student?->first_name ?? '') . ' ' . ($e->student?->last_name ?? '')),
                $e->course?->code,
            ]));
            $this->newLine();
        }

        if (!$this->option('fix')) {
            if ($completedTooEarly->isNotEmpty() || $completedAtInaccurate->isNotEmpty() || $staleEnrollmentStatus->isNotEmpty()) {
                $this->warn('DRY RUN — nothing was changed. Re-run with --fix to correct the fixable categories above (overdue-for-completion rows self-heal on the next finance:process-trimester-transitions run and are never touched here).');
            }
            return self::SUCCESS;
        }

        $toFix = $completedTooEarly->count() + $completedAtInaccurate->count() + $staleEnrollmentStatus->count();

        if ($toFix === 0) {
            $this->info('Nothing to fix.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Fix {$completedTooEarly->count()} completed-too-early, {$completedAtInaccurate->count()} completed_at-inaccurate, and {$staleEnrollmentStatus->count()} stale-enrollment-status row(s)?", false)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        foreach ($completedTooEarly as [$progression, $realEnd]) {
            $progression->update(['status' => 'active', 'completed_at' => null]);
        }

        foreach ($completedAtInaccurate as [$progression, $realEnd]) {
            $progression->update(['completed_at' => $realEnd->toDateString()]);
        }

        $statusService = app(EnrollmentStatusService::class);
        foreach ($staleEnrollmentStatus as $enrollment) {
            $statusService->markCourseCompleted($enrollment);
        }

        $this->info("Fixed {$completedTooEarly->count()} completed-too-early, {$completedAtInaccurate->count()} completed_at-inaccurate, and {$staleEnrollmentStatus->count()} stale-enrollment-status row(s).");

        return self::SUCCESS;
    }

    protected function report(string $title, $rows): void
    {
        $this->components->info($title . " ({$rows->count()})");

        if ($rows->isEmpty()) {
            $this->info('None found.');
            $this->newLine();
            return;
        }

        $table = $rows->map(function ($pair) {
            [$progression, $realEnd] = $pair;
            $student = $progression->student;
            $course = $progression->enrollment?->course;

            return [
                $progression->id,
                trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
                $course?->code,
                $progression->status,
                $progression->started_at?->toDateString(),
                $progression->completed_at?->toDateString() ?? '-',
                $realEnd->toDateString(),
            ];
        });

        $this->table(['Prog #', 'Student', 'Course', 'Status', 'Started', 'Stored completed_at', 'Real end date'], $table);
        $this->newLine();
    }
}
