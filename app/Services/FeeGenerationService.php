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
        $enrollment = $progression->enrollment;

        $plans = CourseFeePlan::query()
            ->where('course_id', $enrollment->course_id)
            ->where(function ($q) use ($progression) {
                $q->where('charge_timing', 'every_trimester')
                    ->orWhere(function ($sub) use ($progression) {
                        $sub->where('charge_timing', 'specific_trimester')
                            ->where('trimester_sequence', $progression->trimester_sequence);
                    })
                    ->orWhere(function ($sub) use ($progression) {
                        $sub->where('charge_timing', 'on_enrollment')
                            ->whereRaw($progression->trimester_sequence === 1 ? '1 = 1' : '1 = 0');
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
    }

    public function generateStudentOnceFees(Enrollment $enrollment): void
    {
        $definitions = FeeDefinition::query()
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->where('active', true)
            ->get();

        foreach ($definitions as $definition) {
            StudentFeeItem::firstOrCreate(
                [
                    'student_id' => $enrollment->student_id,
                    'fee_definition_id' => $definition->id,
                    'enrollment_id' => null,
                    'enrollment_progression_id' => null,
                    'course_fee_plan_id' => null,
                ],
                [
                    'trimester_id' => null,
                    'description' => $definition->name,
                    'amount' => $definition->default_amount,
                    'amount_paid' => 0,
                    'balance' => $definition->default_amount,
                    'charge_date' => $enrollment->admission_date ?? now()->toDateString(),
                    'due_date' => $enrollment->admission_date ?? now()->toDateString(),
                    'status' => 'pending',
                ]
            );
        }
    }

    public function previewInitialCharges(Enrollment $enrollment): array
    {
        $preview = [];

        // ✅ Student once fees
        $definitions = FeeDefinition::query()
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
