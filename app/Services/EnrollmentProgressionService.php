<?php

/* namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Trimester;
use Illuminate\Support\Facades\DB;

class EnrollmentProgressionService
{
    public function generateForEnrollment(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $enrollment->load(['course', 'assignedStartTrimester.academicYear']);

            $duration = (int) ($enrollment->course->number_of_trimesters ?? 0);

            if ($duration <= 0 || ! $enrollment->assignedStartTrimester) {
                return;
            }

            $trimester = $enrollment->assignedStartTrimester;

            for ($sequence = 1; $sequence <= $duration; $sequence++) {
                EnrollmentProgression::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'trimester_sequence' => $sequence,
                    ],
                    [
                        'student_id' => $enrollment->student_id,
                        'trimester_id' => $trimester->id,
                        'status' => $this->resolveProgressionStatus($trimester),
                        'started_at' => $trimester->start_date,
                        'completed_at' => now()->gt($trimester->end_date) ? $trimester->end_date : null,
                    ]
                );

                $trimester = app(AcademicCalendarService::class)
                    ->getOrCreateNextTrimester($trimester);
            }
        });
    }

    protected function resolveProgressionStatus(Trimester $trimester): string
    {
        if (now()->lt($trimester->start_date)) {
            return 'upcoming';
        }

        if (now()->between($trimester->start_date, $trimester->end_date)) {
            return 'active';
        }

        return 'completed';
    }
} */

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Trimester;
use Illuminate\Support\Facades\DB;

class EnrollmentProgressionService
{
    public function createFirstProgression(Enrollment $enrollment): EnrollmentProgression
    {
        return DB::transaction(function () use ($enrollment) {
            $existing = EnrollmentProgression::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('trimester_sequence', 1)
                ->first();

            if ($existing) {
                return $existing;
            }

            return EnrollmentProgression::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'trimester_id' => $enrollment->assigned_start_trimester_id,
                'trimester_sequence' => 1,
                'status' => 'active',
                'started_at' => $enrollment->admission_date,
            ]);
        });
    }

    public function createNextProgression(Enrollment $enrollment): ?EnrollmentProgression
    {
        return DB::transaction(function () use ($enrollment) {
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
                'completed_at' => $lastProgression->completed_at ?? now(),
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
                ]
            );
        });
    }
}
