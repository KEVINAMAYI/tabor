<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\FeeDefinition;
use App\Models\CourseFeePlan;
use App\Models\StudentFeeItem;
use Illuminate\Support\Facades\DB;

class FeeGenerationService
{
    public function generateInitialCharges(Enrollment $enrollment): void
    {

        DB::transaction(function () use ($enrollment) {
            $this->generateStudentOnceFees($enrollment);

            $this->generateChargesForProgression($enrollment->progressions()->where('status', 'active')->orderBy('trimester_sequence')->first());
        });
    }

    public function generateChargesForProgression(EnrollmentProgression $progression): void
    {
        $progression->loadMissing([
            'enrollment.course',
            'trimester',
        ]);

        $enrollment = $progression->enrollment;
        $course = $enrollment->course;

        if (!$course) {
            return;
        }

        $plans = CourseFeePlan::query()
            ->where('course_id', $course->id)
            ->where(function ($q) use ($progression) {
                $q->where('charge_timing', 'every_trimester')

                    ->orWhere(function ($sub) use ($progression) {
                        $sub->where('charge_timing', 'every_trimester_after_first')
                            ->whereRaw('? > 1', [$progression->trimester_sequence]);
                    })

                    ->orWhere(function ($sub) use ($progression) {
                        $sub->where('charge_timing', 'specific_trimester')
                            ->where('trimester_sequence', $progression->trimester_sequence);
                    })

                    ->orWhere(function ($sub) use ($progression) {
                        $sub->where('charge_timing', 'on_enrollment')
                            ->whereRaw('? = 1', [$progression->trimester_sequence]);
                    });
            })
            ->with('feeDefinition')
            ->get();

        foreach ($plans as $plan) {
            $exists = StudentFeeItem::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('enrollment_progression_id', $progression->id)
                ->where('course_fee_plan_id', $plan->id)
                ->exists();

            if ($exists) {
                continue;
            }

            StudentFeeItem::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'enrollment_progression_id' => $progression->id,
                'course_fee_plan_id' => $plan->id,
                'trimester_id' => $progression->trimester_id,
                'fee_definition_id' => $plan->fee_definition_id,
                'description' => $plan->feeDefinition?->name ?? 'Fee',
                'amount' => $plan->amount,
                'amount_paid' => 0,
                'balance' => $plan->amount,
                'charge_date' => $progression->started_at ?? now()->toDateString(),
                'due_date' => $progression->started_at ?? now()->toDateString(),
                'status' => 'pending',
            ]);
        }
    }

    /* public function generateChargesForProgression(EnrollmentProgression $progression): void
    {
        $enrollment = $progression->enrollment;

        $plans = CourseFeePlan::query()
            ->where('course_id', $progression->enrollment->course_id)
            ->where(function ($q) use ($progression) {
                $q->where('charge_timing', 'every_trimester')

                    ->orWhere(function ($sub) use ($progression) {
                        $sub->where('charge_timing', 'every_trimester_after_first')
                            ->whereRaw('? > 1', [$progression->trimester_sequence]);
                    })

                    ->orWhere(function ($sub) use ($progression) {
                        $sub->where('charge_timing', 'specific_trimester')
                            ->where('trimester_sequence', $progression->trimester_sequence);
                    });
            })
            ->with('feeDefinition')
            ->get();

        foreach ($plans as $plan) {
            StudentFeeItem::firstOrCreate(
                [
                    'student_id' => $progression->student_id,
                    'enrollment_id' => $progression->enrollment_id,
                    'enrollment_progression_id' => $progression->id,
                    'course_fee_plan_id' => $plan->id,
                ],
                [
                    'trimester_id' => $progression->trimester_id,
                    'fee_definition_id' => $plan->fee_definition_id,
                    'description' => $plan->feeDefinition?->name ?? 'Course Fee',
                    'amount' => $plan->amount,
                    'amount_paid' => 0,
                    'balance' => $plan->amount,
                    'charge_date' => $progression->started_at ?? now()->toDateString(),
                    'due_date' => $progression->started_at ?? now()->toDateString(),
                    'status' => 'pending',
                ]
            );
        }
    } */

    /* public function generateStudentOnceFees(Enrollment $enrollment): void
    {
        $course = $enrollment->course;

        $chargeableIds = $enrollment->course?->chargeable_student_once_fee_definition_ids ?? [];

        if (empty($chargeableIds)) {
            return;
        }

        $definitions = FeeDefinition::query()
            ->whereIn('id', $chargeableIds)
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->where('active', true)
            ->get();

        foreach ($definitions as $definition) {
            $alreadyCharged = StudentFeeItem::query()
                ->where('student_id', $enrollment->student_id)
                ->where('fee_definition_id', $definition->id)
                ->exists();

            if ($alreadyCharged) {
                continue;
            }

            StudentFeeItem::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'enrollment_progression_id' => null,
                'course_fee_plan_id' => null,
                'trimester_id' => null,
                'fee_definition_id' => $definition->id,
                'description' => $definition->name,
                'amount' => $definition->default_amount,
                'amount_paid' => 0,
                'balance' => $definition->default_amount,
                'charge_date' => $enrollment->admission_date ?? now()->toDateString(),
                'due_date' => $enrollment->admission_date ?? now()->toDateString(),
                'status' => 'pending',
            ]);
        }
    } */

    public function generateStudentOnceFees(Enrollment $enrollment): void
    {
        $course = $enrollment->course;

        if (!$course) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Continuous Intake Rule
        |--------------------------------------------------------------------------
        |
        | If this is a short/continuous course and the student has another
        | non-continuous enrollment in the same intake trimester, student-once
        | fees should be charged on the main/non-continuous enrollment instead.
        |
        */

        if (
            (bool) $course->allows_continuous_intake === true &&
            $this->studentHasMainEnrollmentInSameTrimester($enrollment)
        ) {
            return;
        }

        $chargeableIds = $course->chargeable_student_once_fee_definition_ids ?? [];

        if (empty($chargeableIds)) {
            return;
        }

        $definitions = FeeDefinition::query()
            ->whereIn('id', $chargeableIds)
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->where('active', true)
            ->get();

        $firstProgression = $enrollment->progressions()
            ->where('trimester_sequence', 1)
            ->first();

        if (!$firstProgression) {
            return;
        }

        foreach ($definitions as $definition) {
            $alreadyCharged = StudentFeeItem::query()
                ->where('student_id', $enrollment->student_id)
                ->where('fee_definition_id', $definition->id)
                ->exists();

            if ($alreadyCharged) {
                continue;
            }

            StudentFeeItem::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'enrollment_progression_id' => $firstProgression?->id,
                'course_fee_plan_id' => null,
                'trimester_id' => $firstProgression?->trimester_id,
                'fee_definition_id' => $definition->id,
                'description' => $definition->name,
                'amount' => $definition->default_amount,
                'amount_paid' => 0,
                'balance' => $definition->default_amount,
                'charge_date' => $enrollment->admission_date ?? now()->toDateString(),
                'due_date' => $enrollment->admission_date ?? now()->toDateString(),
                'status' => 'pending',
            ]);
        }
    }

    protected function studentHasMainEnrollmentInSameTrimester(Enrollment $enrollment): bool
    {
        if (!$enrollment->intake_trimester_id) {
            return false;
        }

        return Enrollment::query()
            ->where('student_id', $enrollment->student_id)
            ->where('id', '!=', $enrollment->id)
            ->where('intake_trimester_id', $enrollment->intake_trimester_id)
            ->whereIn('status', [
                'active',
                'course_completed',
                'pending_graduation',
                'graduated',
            ])
            ->whereHas('course', function ($q) {
                $q->where(function ($courseQuery) {
                    $courseQuery->where('allows_continuous_intake', false)
                        ->orWhereNull('allows_continuous_intake');
                });
            })
            ->exists();
    }
    public function previewInitialCharges(Enrollment $enrollment): array
    {
        $preview = [];

        // ✅ Student once fees
        $course = $enrollment->course;

        $chargeableIds = $course?->chargeable_student_once_fee_definition_ids ?? [];

        $definitions = FeeDefinition::query()
            ->whereIn('id', $chargeableIds)
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->where('active', true)
            ->get();

        foreach ($definitions as $definition) {
            $alreadyExists = StudentFeeItem::query()
                ->where('student_id', $enrollment->student_id)
                ->where('fee_definition_id', $definition->id)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $preview[] = [
                'type' => 'Student',
                'name' => $definition->name,
                'amount' => $definition->default_amount,
            ];
        }

        // ✅ Trimester 1 fees
        $plans = CourseFeePlan::query()
            ->where('course_id', $enrollment->course_id)
            ->where(function ($q) {
                $q->where('charge_timing', 'on_enrollment')
                    ->orWhere('charge_timing', 'every_trimester')
                    ->orWhere(function ($sub) {
                        $sub->where('charge_timing', 'specific_trimester')
                            ->where('trimester_sequence', 1);
                    });
            })
            ->with('feeDefinition')
            ->get();

        foreach ($plans as $plan) {

            $preview[] = [
                'type' => 'Trimester',
                'name' => $plan->feeDefinition?->name ?? 'Trimester',
                'amount' => $plan->amount,
            ];
        }

        return $preview;
    }
}
