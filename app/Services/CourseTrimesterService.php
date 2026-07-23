<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseTrimester;

class CourseTrimesterService
{
    /**
     * Create or sync course trimesters based on course structure.
     * SAFE: affects only course templates, not enrollments.
     */
    public static function syncCourseTrimesters(Course $course): void
    {
        // Guard clause
        if ($course->number_of_trimesters < 1) {
            return;
        }



        $feePerTrimester = $course->price;
        $durationPerTrimester = self::toMonths($course->duration) / $course->number_of_trimesters;

        // 1️⃣ Create / Update required trimesters
        for ($i = 1; $i <= $course->number_of_trimesters; $i++) {
            CourseTrimester::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'trimester_number' => $i,
                ],
                [
                    'duration_months' => $durationPerTrimester,
                    'fee_amount' => $feePerTrimester,
                ]
            );
        }

        // 2️⃣ Delete excess trimesters (if number reduced)
        CourseTrimester::where('course_id', $course->id)
            ->where('trimester_number', '>', $course->number_of_trimesters)
            ->delete();
    }

    public static function toMonths(string $duration): int
    {
        $duration = strtolower($duration);

        $months = 0;

        // 1️⃣ Handle ranges like "16-18 weeks"
        if (preg_match('/(\d+)\s*-\s*(\d+)\s*(week|weeks)/', $duration, $m)) {
            return (int) ceil($m[2] / 4);
        }

        // 2️⃣ Years → months
        if (preg_match_all('/(\d+(\.\d+)?)\s*year/', $duration, $matches)) {
            foreach ($matches[1] as $value) {
                $months += (float) $value * 12;
            }
        }

        // 3️⃣ Months
        if (preg_match_all('/(\d+(\.\d+)?)\s*month/', $duration, $matches)) {
            foreach ($matches[1] as $value) {
                $months += (float) $value;
            }
        }

        // 4️⃣ Weeks → months
        if (preg_match_all('/(\d+)\s*week/', $duration, $matches)) {
            foreach ($matches[1] as $value) {
                $months += ceil($value / 4);
            }
        }

        return (int) round($months);
    }
}
