<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Trimester;
use Carbon\Carbon;

class TrimesterAssignmentService
{
    public function assign(Carbon $admissionDate, $course_id): array
    {
        $course = Course::findOrFail($course_id);

        $currentTrimester = Trimester::query()
            ->whereDate('start_date', '<=', $admissionDate)
            ->whereDate('end_date', '>=', $admissionDate)
            ->first();

        if (!$currentTrimester) {
            $nextTrimester = Trimester::query()
                ->whereDate('start_date', '>', $admissionDate)
                ->orderBy('start_date')
                ->firstOrFail();

            return [
                'intake_trimester_id' => $nextTrimester->id,
                'assigned_start_trimester_id' => $nextTrimester->id,
            ];
        }

        $assignedTrimester = $currentTrimester;

        if (
            $course->allows_continuous_intake &&
            $currentTrimester->intake_cutoff_date &&
            $admissionDate->gt($currentTrimester->intake_cutoff_date)
        ) {
            $assignedTrimester = Trimester::query()
                ->whereDate('start_date', '>', $currentTrimester->end_date)
                ->orderBy('start_date')
                ->firstOrFail();
        }

        return [
            'intake_trimester_id' => $currentTrimester->id,
            'assigned_start_trimester_id' => $assignedTrimester->id,
        ];
    }
}
