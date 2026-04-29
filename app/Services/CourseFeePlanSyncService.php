<?php

namespace App\Services;

use App\Models\Course;
use App\Models\FeeDefinition;
use App\Models\CourseFeePlan;

class CourseFeePlanSyncService
{
    public function syncDefaultsForCourse(Course $course): void
    {
        $this->syncRecurringTuition($course);

        $this->syncOptionalCompletionFee(
            course: $course,
            enabledField: 'apply_graduation_fee',
            amountField: 'graduation_fee',
            feeName: 'Graduation Fee',
            charge_timing: 'on_completion'
        );

        $this->syncOptionalCompletionFee(
            course: $course,
            enabledField: 'apply_attachment_fee',
            amountField: 'attachment_fee',
            feeName: 'Attachment Fee',
            charge_timing: 'specific_trimester'
        );

        $this->syncOptionalCompletionFee(
            course: $course,
            enabledField: 'apply_certification_fee',
            amountField: 'certification_fee',
            feeName: 'Certification Fee',
            charge_timing: 'on_completion'
        );
        $this->syncOptionalCompletionFee(
            course: $course,
            enabledField: 'apply_exam_fee',
            amountField: 'exam_fee',
            feeName: 'Exam Fee',
            charge_timing: 'every_trimester'
        );
    }

    protected function syncRecurringTuition(Course $course): void
    {
        if (blank($course->price) || $course->price <= 0) {
            return;
        }

        $tuitionFee = FeeDefinition::query()
            ->where('slug', 'tuition_fee')
            ->where('scope', 'trimester')
            ->where('active', true)
            ->first();

        if (!$tuitionFee) {
            return;
        }

        CourseFeePlan::updateOrCreate(
            [
                'course_id' => $course->id,
                'fee_definition_id' => $tuitionFee->id,
                'charge_timing' => 'every_trimester',
                'trimester_sequence' => null,
            ],
            [
                'amount' => $course->price,
                'mandatory' => true,
            ]
        );
    }

    protected function syncOptionalCompletionFee(
        Course $course,
        string $enabledField,
        string $amountField,
        string $feeName,
        string $charge_timing
    ): void {
        $isEnabled = (bool) data_get($course, $enabledField);
        $amount = data_get($course, $amountField);

        $feeDefinition = FeeDefinition::query()
            ->where('name', $feeName)
            ->where('active', true)
            ->first();

        if (!$feeDefinition) {
            return;
        }

        $existingPlan = CourseFeePlan::query()
            ->where('course_id', $course->id)
            ->where('fee_definition_id', $feeDefinition->id)
            ->where('charge_timing', $charge_timing)
            ->first();

        if (!$isEnabled || blank($amount) || $amount <= 0) {
            if ($existingPlan) {
                $existingPlan->delete();
            }

            return;
        }

        CourseFeePlan::updateOrCreate(
            [
                'course_id' => $course->id,
                'fee_definition_id' => $feeDefinition->id,
                'charge_timing' => $charge_timing,
            ],
            [
                'trimester_sequence' => null,
                'amount' => $amount,
                'mandatory' => true,
            ]
        );
    }
}
