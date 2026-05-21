<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Trimester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentProgressionService
{
    public function createFirstProgression(Enrollment $enrollment): EnrollmentProgression
    {
        return DB::transaction(function () use ($enrollment) {
            $enrollment->loadMissing(['course']);

            $existing = EnrollmentProgression::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('trimester_sequence', 1)
                ->first();

            if ($existing) {
                return $existing;
            }

            $startedAt = Carbon::parse(
                $enrollment->admission_date ?? now()
            );

            $completedAt = null;

            if ((bool) $enrollment->course?->allows_continuous_intake) {
                $completedAt = $startedAt
                    ->copy()
                    ->addMonths(3)
                    ->subDay()
                    ->toDateString();
            }

            return EnrollmentProgression::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'trimester_id' => $enrollment->assigned_start_trimester_id,
                'trimester_sequence' => 1,
                'status' => $completedAt && now()->gt($completedAt)
                    ? 'completed'
                    : 'active',
                'started_at' => $startedAt->toDateString(),
                'completed_at' => $completedAt && now()->gt($completedAt)
                    ? $completedAt
                    : null,
            ]);
        });
    }

    public function createNextProgression(Enrollment $enrollment): ?EnrollmentProgression
    {
        return DB::transaction(function () use ($enrollment) {
            $enrollment->loadMissing(['course']);

            /*
            |--------------------------------------------------------------------------
            | Continuous Intake Courses
            |--------------------------------------------------------------------------
            |
            | Short courses should remain one 3-month progression.
            | They should not generate next trimester progressions.
            |
            */

            if ((bool) $enrollment->course?->allows_continuous_intake) {
                return null;
            }

            $lastProgression = EnrollmentProgression::query()
                ->where('enrollment_id', $enrollment->id)
                ->orderByDesc('trimester_sequence')
                ->lockForUpdate()
                ->first();

            if (!$lastProgression) {
                return $this->createFirstProgression($enrollment);
            }

            $nextSequence = (int) $lastProgression->trimester_sequence + 1;

            if ($nextSequence > (int) $enrollment->course->number_of_trimesters) {
                return null;
            }

            $nextTrimester = Trimester::query()
                ->whereDate('start_date', '>', $lastProgression->trimester?->start_date)
                ->orderBy('start_date')
                ->first();

            if (!$nextTrimester) {
                return null;
            }

            $lastProgression->update([
                'status' => 'completed',
                'completed_at' => $lastProgression->completed_at
                    ?? $lastProgression->trimester?->end_date
                    ?? now()->toDateString(),
            ]);

            return EnrollmentProgression::firstOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'trimester_sequence' => $nextSequence,
                ],
                [
                    'student_id' => $enrollment->student_id,
                    'trimester_id' => $nextTrimester->id,
                    'status' => 'active',
                    'started_at' => $nextTrimester->start_date,
                    'completed_at' => null,
                ]
            );
        });
    }
}
