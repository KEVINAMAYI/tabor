<?php

namespace App\Services;

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
}
