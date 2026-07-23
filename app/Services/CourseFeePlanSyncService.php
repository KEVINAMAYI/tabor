<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseFeePlan;
use App\Models\FeeDefinition;

class CourseFeePlanSyncService
{
    public function syncDefaultsForCourse(Course $course, bool $overwriteExisting = false): void
    {
        $this->syncTuition($course, $overwriteExisting);

        $this->syncOptionalFee(
            course: $course,
            enabledField: 'apply_exam_fee',
            amountField: 'exam_fee',
            feeSlug: 'exam_fee',
            feeName: 'Exam Fee',
            chargeTiming: $this->examFeeTimingForCourse($course),
            trimesterSequence: null,
            overwriteExisting: $overwriteExisting
        );

        $this->syncOptionalFee(
            course: $course,
            enabledField: 'apply_attachment_fee',
            amountField: 'attachment_fee',
            feeSlug: 'attachment_fee',
            feeName: 'Attachment Fee',
            chargeTiming: 'specific_trimester',
            trimesterSequence: 2,
            overwriteExisting: $overwriteExisting
        );

        $this->syncOptionalFee(
            course: $course,
            enabledField: 'apply_graduation_fee',
            amountField: 'graduation_fee',
            feeSlug: 'graduation_fee',
            feeName: 'Graduation Fee',
            chargeTiming: 'on_graduation_processing',
            trimesterSequence: null,
            overwriteExisting: $overwriteExisting
        );

        $this->syncOptionalFee(
            course: $course,
            enabledField: 'apply_certification_fee',
            amountField: 'certification_fee',
            feeSlug: 'certification_fee',
            feeName: 'Certification Fee',
            chargeTiming: 'on_course_completion',
            trimesterSequence: null,
            overwriteExisting: $overwriteExisting
        );
    }

    protected function syncTuition(Course $course, bool $overwriteExisting): void
    {
        if (blank($course->price) || $course->price <= 0) {
            return;
        }

        $feeDefinition = $this->findFeeDefinition('tuition_fee', 'Tuition Fee');

        if (!$feeDefinition) {
            return;
        }

        $this->createOrUpdatePlan(
            course: $course,
            feeDefinition: $feeDefinition,
            chargeTiming: 'every_trimester',
            trimesterSequence: null,
            amount: $course->price,
            overwriteExisting: $overwriteExisting
        );
    }

    protected function syncOptionalFee(
        Course $course,
        string $enabledField,
        string $amountField,
        string $feeSlug,
        string $feeName,
        string $chargeTiming,
        ?int $trimesterSequence,
        bool $overwriteExisting
    ): void {
        $isEnabled = (bool) data_get($course, $enabledField);
        $amount = (float) data_get($course, $amountField);

        $feeDefinition = $this->findFeeDefinition($feeSlug, $feeName);

        if (!$feeDefinition) {
            return;
        }

        $existingPlan = CourseFeePlan::query()
            ->where('course_id', $course->id)
            ->where('fee_definition_id', $feeDefinition->id)
            ->where('charge_timing', $chargeTiming)
            ->when(
                $trimesterSequence === null,
                fn($q) => $q->whereNull('trimester_sequence'),
                fn($q) => $q->where('trimester_sequence', $trimesterSequence)
            )
            ->first();

        if (!$isEnabled || $amount <= 0) {
            if ($overwriteExisting && $existingPlan) {
                $existingPlan->delete();
            }

            return;
        }

        $this->createOrUpdatePlan(
            course: $course,
            feeDefinition: $feeDefinition,
            chargeTiming: $chargeTiming,
            trimesterSequence: $trimesterSequence,
            amount: $amount,
            overwriteExisting: $overwriteExisting
        );
    }

    protected function examFeeTimingForCourse(Course $course): string
    {
        $categoryName = strtolower($course->category?->slug ?? '');

        $isHealthcare = str_contains($categoryName, 'healthcare')
            || str_contains($categoryName, 'health care');

        $isMoreThanOneYear = (int) $course->number_of_trimesters > 3;

        return $isHealthcare && $isMoreThanOneYear
            ? 'every_trimester_after_first'
            : 'every_trimester';
    }

    protected function createOrUpdatePlan(
        Course $course,
        FeeDefinition $feeDefinition,
        string $chargeTiming,
        ?int $trimesterSequence,
        float $amount,
        bool $overwriteExisting
    ): void {
        $query = CourseFeePlan::query()
            ->where('course_id', $course->id)
            ->where('fee_definition_id', $feeDefinition->id)
            ->where('charge_timing', $chargeTiming)
            ->when(
                $trimesterSequence === null,
                fn($q) => $q->whereNull('trimester_sequence'),
                fn($q) => $q->where('trimester_sequence', $trimesterSequence)
            );

        $existingPlan = $query->first();

        if ($existingPlan && !$overwriteExisting) {
            return;
        }

        CourseFeePlan::updateOrCreate(
            [
                'course_id' => $course->id,
                'fee_definition_id' => $feeDefinition->id,
                'charge_timing' => $chargeTiming,
                'trimester_sequence' => $trimesterSequence,
            ],
            [
                'amount' => $amount,
                'mandatory' => true,
            ]
        );
    }

    protected function findFeeDefinition(string $slug, string $name): ?FeeDefinition
    {
        return FeeDefinition::query()
            ->where('active', true)
            ->where(function ($query) use ($slug, $name) {
                $query->where('slug', $slug)
                    ->orWhere('name', $name);
            })
            ->first();
    }
}
