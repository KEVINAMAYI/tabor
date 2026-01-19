<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\CourseTrimester;
use App\Models\EnrollmentTrimester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicTrimester;

class EnrollmentService
{
    /**
     * Create enrollment and its trimesters atomically.
     */
    public static function enrollStudent($studentId, $courseId, $intakeId, $status): Enrollment
    {

        // 1️⃣ Create enrollment
        $enrollment = Enrollment::create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'enrolled_at' => now(),
            'status' => $status,
        ]);
        /* // 2️⃣ Create enrollment trimesters
        self::createEnrollmentTrimesters($enrollment); */

        return $enrollment;

    }

    /**
     * Create enrollment trimesters (IMMUTABLE after creation)
     */
    public static function createEnrollmentTrimesters(Enrollment $enrollment): void
    {
        // 🔒 Guard: prevent double creation
        if ($enrollment->trimesters()->exists()) {
            throw new \LogicException('Enrollment trimesters already exist.');
        }

        $courseTrimesters = CourseTrimester::where('course_id', $enrollment->course_id)
            ->orderBy('trimester_number')
            ->get();

        if ($courseTrimesters->isEmpty()) {
            throw new \LogicException("Course " . $enrollment->course_id . " has no trimester structure.");
        }

        $startDate = Carbon::parse($enrollment->intake->starts_at);
        $course = $enrollment->course;
        $usesAcademicCalendar = (bool) $course->uses_academic_calendar;
        $today = now();
        if ($usesAcademicCalendar) {

            // Find academic trimester where intake falls
            $academicTrimester = AcademicTrimester::whereDate('teaching_start_date', '<=', $startDate)
                ->whereDate('teaching_end_date', '>=', $startDate)
                ->first();

            // If intake is during holiday, push to next trimester
            if (!$academicTrimester) {
                $academicTrimester = AcademicTrimester::whereDate('teaching_start_date', '>', $startDate)
                    ->orderBy('teaching_start_date')
                    ->first();
            }

            if (!$academicTrimester) {
                throw new \LogicException('No academic trimester found for start date.');
            }

            // 2-month cutoff rule
            $monthsIntoTrimester = $academicTrimester->teaching_start_date
                ->diffInMonths($startDate);

            if ($monthsIntoTrimester >= 2) {
                $academicTrimester = AcademicTrimester::where(
                    'teaching_start_date',
                    '>',
                    $academicTrimester->teaching_start_date
                )
                    ->orderBy('teaching_start_date')
                    ->first();
            }

            $currentAcademicTrimester = $academicTrimester;

            /* ======================================================
           3️⃣ CREATE ENROLLMENT TRIMESTERS (ACADEMIC)
           ====================================================== */

            foreach ($courseTrimesters as $courseTrimester) {
                if (!$currentAcademicTrimester) {
                    throw new \LogicException('Not enough academic trimesters to cover course duration.');
                }

                EnrollmentTrimester::create([
                    'enrollment_id' => $enrollment->id,
                    'academic_trimester_id' => $currentAcademicTrimester->id,
                    'trimester_number' => $courseTrimester->trimester_number,
                    'start_date' => $currentAcademicTrimester->teaching_start_date,
                    'end_date' => $currentAcademicTrimester->holiday_end_date,
                    'status' => $today->gt($currentAcademicTrimester->holiday_end_date)
                        ? 'completed'
                        : ($today->between($currentAcademicTrimester->teaching_start_date, $currentAcademicTrimester->holiday_end_date)
                            ? 'in-progress'
                            : 'pending'),
                    'fee_amount' => $courseTrimester->fee_amount,
                ]);

                // Move to the NEXT academic trimester for the next enrollment trimester
                $currentAcademicTrimester = AcademicTrimester::where(
                    'teaching_start_date',
                    '>',
                    $currentAcademicTrimester->teaching_start_date
                )
                    ->orderBy('teaching_start_date')
                    ->first();

            }
        } else {

            foreach ($courseTrimesters as $index => $courseTrimester) {

                $trimesterStart = $startDate->copy();
                $trimesterEnd = $trimesterStart->copy()
                    ->addMonths($courseTrimester->duration_months);
                EnrollmentTrimester::create([
                    'enrollment_id' => $enrollment->id,
                    'course_trimester_id' => $courseTrimester->id,
                    'trimester_number' => $courseTrimester->trimester_number,
                    'start_date' => $trimesterStart,
                    'end_date' => $trimesterEnd,
                    // 'status' => $index === 0 ? 'in-progress' : 'pending',
                    'status' => $today->gt($trimesterEnd)
                        ? 'completed'
                        : ($today->between($trimesterStart, $trimesterEnd)
                            ? 'in-progress'
                            : 'pending'),
                    'fee_amount' => $courseTrimester->fee_amount,
                ]);

                // move pointer forward
                $startDate = $trimesterEnd->copy()->addDay();
            }
        }

        $remaining = EnrollmentTrimester::where('enrollment_id', $enrollment->id)->where('status', '!=', 'completed')->exists();

        if (!$remaining) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => $today,
            ]);
        }
    }

}
