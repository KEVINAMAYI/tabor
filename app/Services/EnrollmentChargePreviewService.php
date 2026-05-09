<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseFeePlan;
use App\Models\FeeDefinition;
use App\Models\Student;
use App\Models\StudentFeeItem;

class EnrollmentChargePreviewService
{
    public function preview(Student $student, Course $course): array
    {
        $items = [];

        $studentOnceFeeIds = $course->chargeable_student_once_fee_definition_ids ?? [];

        $studentOnceFees = FeeDefinition::query()
            ->whereIn('id', $studentOnceFeeIds)
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->where('active', true)
            ->get();

        foreach ($studentOnceFees as $fee) {
            $alreadyCharged = StudentFeeItem::query()
                ->where('student_id', $student->id)
                ->where('fee_definition_id', $fee->id)
                ->exists();

            if (!$alreadyCharged) {
                $items[] = [
                    'type' => 'Student Once',
                    'description' => $fee->name,
                    'timing' => 'Once in student lifetime',
                    'amount' => (float) $fee->default_amount,
                ];
            }
        }

        $plans = CourseFeePlan::query()
            ->where('course_id', $course->id)
            ->whereIn('charge_timing', ['on_enrollment', 'every_trimester', 'specific_trimester'])
            ->with('feeDefinition')
            ->orderByRaw("
                FIELD(charge_timing, 'on_enrollment', 'every_trimester', 'specific_trimester')
            ")
            ->orderBy('trimester_sequence')
            ->get();

        foreach ($plans as $plan) {
            $items[] = [
                'type' => 'Course Fee',
                'description' => $plan->feeDefinition?->name ?? 'Fee',
                'timing' => match ($plan->charge_timing) {
                    'on_enrollment' => 'On enrollment',
                    'every_trimester' => 'Every trimester',
                    'specific_trimester' => 'Trimester ' . $plan->trimester_sequence,
                    default => $plan->charge_timing,
                },
                'amount' => (float) $plan->amount,
            ];
        }

        return $items;
    }
}
