<?php

namespace App\Actions\Finance;

use App\Models\EnrollmentProgression;
use App\Models\FeeDefinition;
use App\Models\StudentFeeItem;
use Illuminate\Support\Facades\DB;

class ApplyStudentDiscountAction
{
    public function execute(EnrollmentProgression $progression, array $data): StudentFeeItem
    {
        return DB::transaction(function () use ($progression, $data) {
            $amount = abs((float) $data['amount']);

            if ($amount <= 0) {
                throw new \RuntimeException('Discount amount must be greater than zero.');
            }

            $feeDefinition = FeeDefinition::query()
                ->where('name', 'Discount')
                ->first();

            if (! $feeDefinition) {
                $feeDefinition = FeeDefinition::create([
                    'name' => 'Discount',
                    'scope' => 'enrollment',
                    'applies_once' => false,
                    'active' => true,
                    'default_amount' => 0,
                ]);
            }

            return StudentFeeItem::create([
                'student_id' => $progression->student_id,
                'enrollment_id' => $progression->enrollment_id,
                'enrollment_progression_id' => $progression->id,
                'course_fee_plan_id' => null,
                'trimester_id' => $progression->trimester_id,
                'fee_definition_id' => $feeDefinition->id,
                'description' => $data['description'] ?? 'Discount',
                'amount' => -$amount,
                'amount_paid' => 0,
                'balance' => -$amount,
                'charge_date' => $data['discount_date'] ?? now()->toDateString(),
                'due_date' => null,
                'status' => 'paid',
            ]);
        });
    }
}
