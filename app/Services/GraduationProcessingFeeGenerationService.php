<?php

namespace App\Services;

use App\Models\CourseFeePlan;
use App\Models\Enrollment;
use App\Models\StudentFeeItem;
use Illuminate\Support\Facades\DB;

class GraduationProcessingFeeGenerationService
{
    public function generate(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $plans = CourseFeePlan::query()
                ->where('course_id', $enrollment->course_id)
                ->where('charge_timing', 'on_graduation_processing')
                ->with('feeDefinition')
                ->get();

            foreach ($plans as $plan) {
                $exists = StudentFeeItem::query()
                    ->where('student_id', $enrollment->student_id)
                    ->where('enrollment_id', $enrollment->id)
                    ->where('course_fee_plan_id', $plan->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                StudentFeeItem::create([
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'enrollment_progression_id' => null,
                    'course_fee_plan_id' => $plan->id,
                    'trimester_id' => null,
                    'fee_definition_id' => $plan->fee_definition_id,
                    'description' => $plan->feeDefinition?->name ?? 'Graduation Processing Fee',
                    'amount' => $plan->amount,
                    'amount_paid' => 0,
                    'balance' => $plan->amount,
                    'charge_date' => now()->toDateString(),
                    'due_date' => now()->toDateString(),
                    'status' => 'pending',
                ]);
            }
        });
    }
}
