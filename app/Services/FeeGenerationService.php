<?php
namespace App\Services\Finance;

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
            $this->generateEnrollmentStartFees($enrollment);
        });
    }

    protected function generateStudentOnceFees(Enrollment $enrollment): void
    {
        $studentFeeDefinitions = FeeDefinition::query()
            ->where('scope', 'student')
            ->where('applies_once', true)
            ->where('active', true)
            ->get();

        foreach ($studentFeeDefinitions as $definition) {
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

    protected function generateEnrollmentStartFees(Enrollment $enrollment): void
    {
        $plans = CourseFeePlan::query()
            ->where('course_id', $enrollment->course_id)
            ->where(function ($q) {
                $q->where('charge_timing', 'on_enrollment')
                    ->orWhere(function ($sub) {
                        $sub->where('charge_timing', 'specific_trimester')
                            ->where('trimester_sequence', 1);
                    })
                    ->orWhere('charge_timing', 'every_trimester');
            })
            ->with('feeDefinition')
            ->get();

        foreach ($plans as $plan) {
            $exists = StudentFeeItem::query()
                ->where('student_id', $enrollment->student_id)
                ->where('enrollment_id', $enrollment->id)
                ->where('course_fee_plan_id', $plan->id)
                ->where('trimester_id', $enrollment->assigned_start_trimester_id)
                ->exists();

            if ($exists) {
                continue;
            }

            StudentFeeItem::create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'trimester_id' => $enrollment->assigned_start_trimester_id,
                'fee_definition_id' => $plan->fee_definition_id,
                'course_fee_plan_id' => $plan->id,
                'description' => $plan->feeDefinition->name,
                'amount' => $plan->amount,
                'amount_paid' => 0,
                'balance' => $plan->amount,
                'charge_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'status' => 'pending',
            ]);
        }
    }
}