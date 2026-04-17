<?php

namespace App\Services;

use App\Models\Enrollment;
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

            $this->generateChargesForTrimester(
                enrollment: $enrollment,
                trimesterId: $enrollment->assigned_start_trimester_id,
                trimesterSequence: $enrollment->assigned_start_trimester_id
            );
        });
    }

    public function generateChargesForTrimester(
        Enrollment $enrollment,
        int $trimesterId,
        int $trimesterSequence
    ): void {
        $plans = CourseFeePlan::query()
            ->where('course_id', $enrollment->course_id)
            ->where(function ($q) use ($trimesterSequence) {
                $q->where('charge_timing', 'every_trimester')
                    ->orWhere(function ($sub) use ($trimesterSequence) {
                        $sub->where('charge_timing', 'specific_trimester')
                            ->where('trimester_sequence', $trimesterSequence);
                    })
                    ->orWhere(function ($sub) use ($trimesterSequence) {
                        $sub->where('charge_timing', 'on_enrollment')
                            ->where($trimesterSequence === 1 ? fn($q) => $q : fn($q) => $q->whereRaw('1 = 0'));
                    });
            })
            ->with('feeDefinition')
            ->get();

        foreach ($plans as $plan) {
            $alreadyExists = StudentFeeItem::query()
                ->where('student_id', $enrollment->student_id)
                ->where('enrollment_id', $enrollment->id)
                ->where('course_fee_plan_id', $plan->id)
                ->where('trimester_id', $trimesterId)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            StudentFeeItem::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'trimester_id' => $trimesterId,
                'fee_definition_id' => $plan->fee_definition_id,
                'course_fee_plan_id' => $plan->id,
                'description' => $plan->feeDefinition?->name ?? 'Course Fee',
                'amount' => $plan->amount,
                'amount_paid' => 0,
                'balance' => $plan->amount,
                'charge_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'status' => 'pending',
            ]);
        }
    }

    protected function generateStudentOnceFees(Enrollment $enrollment): void
    {
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

            StudentFeeItem::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => null,
                'trimester_id' => null,
                'fee_definition_id' => $definition->id,
                'course_fee_plan_id' => null,
                'description' => $definition->name,
                'amount' => $definition->default_amount,
                'amount_paid' => 0,
                'balance' => $definition->default_amount,
                'charge_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'status' => 'pending',
            ]);
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