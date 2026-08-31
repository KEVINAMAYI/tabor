<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\FeeDefinition;
use App\Models\CourseFeePlan;
use App\Models\Payment;
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
        // Never charge a deferred or cancelled progression
        if (in_array($progression->status, ['deferred', 'cancelled', 'repeated'], true)) {
            return;
        }

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
                    })
                    // on_course_completion and on_graduation_processing are charged
                    // explicitly via generateCourseCompletionCharges() / generateGraduationCharges()
                    // and intentionally excluded from normal per-trimester generation.
                    ;
            })
            ->with('feeDefinition')
            ->get();

        $newItemsCreated = 0;

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

            $newItemsCreated++;
        }

        // Apply any prior unallocated payment balances to the new fee items.
        // Payments posted before this progression's items were generated leave an
        // unallocated_balance that would otherwise only be consumed when the NEXT
        // payment arrives — causing the "payment goes to wrong trimester" problem.
        if ($newItemsCreated > 0) {
            $this->applyUnallocatedPayments($enrollment);
        }
    }

    protected function applyUnallocatedPayments(Enrollment $enrollment): void
    {
        $payments = Payment::query()
            ->where('student_id', $enrollment->student_id)
            ->where(function ($q) use ($enrollment) {
                $q->where('enrollment_id', $enrollment->id)
                    ->orWhereNull('enrollment_id');
            })
            ->where('unallocated_balance', '>', 0.01)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            return;
        }

        $postingService = app(\App\Services\PaymentPostingService::class);

        foreach ($payments as $payment) {
            $postingService->allocateExistingPayment($payment);
        }
    }

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
    public function generateCourseCompletionCharges(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $lastProgression = $enrollment->progressions()
                ->orderByDesc('trimester_sequence')
                ->first();

            $plans = CourseFeePlan::query()
                ->where('course_id', $enrollment->course_id)
                ->whereIn('charge_timing', ['on_completion', 'on_course_completion'])
                ->with('feeDefinition')
                ->get();

            foreach ($plans as $plan) {
                $exists = StudentFeeItem::query()
                    ->where('enrollment_id', $enrollment->id)
                    ->where('course_fee_plan_id', $plan->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                StudentFeeItem::create([
                    'student_id'               => $enrollment->student_id,
                    'enrollment_id'            => $enrollment->id,
                    'enrollment_progression_id' => $lastProgression?->id,
                    'course_fee_plan_id'       => $plan->id,
                    'trimester_id'             => $lastProgression?->trimester_id,
                    'fee_definition_id'        => $plan->fee_definition_id,
                    'description'              => $plan->feeDefinition?->name ?? 'Completion Fee',
                    'amount'                   => $plan->amount,
                    'amount_paid'              => 0,
                    'balance'                  => $plan->amount,
                    'charge_date'              => now()->toDateString(),
                    'due_date'                 => now()->toDateString(),
                    'status'                   => 'pending',
                ]);
            }
        });
    }

    public function generateGraduationCharges(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $lastProgression = $enrollment->progressions()
                ->orderByDesc('trimester_sequence')
                ->first();

            $plans = CourseFeePlan::query()
                ->where('course_id', $enrollment->course_id)
                ->where('charge_timing', 'on_graduation_processing')
                ->with('feeDefinition')
                ->get();

            foreach ($plans as $plan) {
                $exists = StudentFeeItem::query()
                    ->where('enrollment_id', $enrollment->id)
                    ->where('course_fee_plan_id', $plan->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                StudentFeeItem::create([
                    'student_id'               => $enrollment->student_id,
                    'enrollment_id'            => $enrollment->id,
                    'enrollment_progression_id' => $lastProgression?->id,
                    'course_fee_plan_id'       => $plan->id,
                    'trimester_id'             => $lastProgression?->trimester_id,
                    'fee_definition_id'        => $plan->fee_definition_id,
                    'description'              => $plan->feeDefinition?->name ?? 'Graduation Processing Fee',
                    'amount'                   => $plan->amount,
                    'amount_paid'              => 0,
                    'balance'                  => $plan->amount,
                    'charge_date'              => now()->toDateString(),
                    'due_date'                 => now()->toDateString(),
                    'status'                   => 'pending',
                ]);
            }
        });
    }

    /**
     * Apply admin-edited fee overrides (from EnrollmentChargePreviewService's preview,
     * possibly edited/removed in the review UI) to the fee items just generated for a
     * freshly created enrollment. Items present in $overrides get their amount/balance
     * updated; auto-generated items the admin removed from the preview get deleted.
     */
    public function applyFeeOverrides(Enrollment $enrollment, array $overrides): void
    {
        $keepIds = [];

        foreach ($overrides as $item) {
            $amount = round((float) ($item['amount'] ?? 0), 2);

            if (empty($item['fee_definition_id'])) {
                // Custom, student-specific charge added in the review UI — not tied to
                // any FeeDefinition/CourseFeePlan, so create it directly rather than
                // trying to match it to an auto-generated item.
                $description = trim((string) ($item['description'] ?? ''));

                if ($description === '') {
                    continue;
                }

                $firstProgression = $enrollment->progressions()
                    ->where('trimester_sequence', 1)
                    ->first();

                $customItem = StudentFeeItem::create([
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'enrollment_progression_id' => $firstProgression?->id,
                    'course_fee_plan_id' => null,
                    'trimester_id' => $firstProgression?->trimester_id,
                    'fee_definition_id' => null,
                    'description' => $description,
                    'amount' => $amount,
                    'amount_paid' => 0,
                    'balance' => $amount,
                    'charge_date' => $enrollment->admission_date ?? now()->toDateString(),
                    'due_date' => $enrollment->admission_date ?? now()->toDateString(),
                    'status' => 'pending',
                ]);

                $keepIds[] = $customItem->id;
                continue;
            }

            $feeItem = StudentFeeItem::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('fee_definition_id', $item['fee_definition_id'])
                ->when(
                    !empty($item['course_fee_plan_id']),
                    fn ($q) => $q->where('course_fee_plan_id', $item['course_fee_plan_id']),
                    fn ($q) => $q->whereNull('course_fee_plan_id'),
                )
                ->first();

            if (!$feeItem) {
                continue;
            }

            $keepIds[] = $feeItem->id;

            $newAmount = round((float) $item['amount'], 2);

            if ((float) $feeItem->amount !== $newAmount) {
                $feeItem->update([
                    'amount' => $newAmount,
                    'balance' => $newAmount - $feeItem->amount_paid,
                ]);
            }
        }

        // Anything auto-generated but absent from the (edited) overrides list was
        // removed by the admin in the review UI. Only ever touch fresh, unpaid items.
        StudentFeeItem::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('amount_paid', 0)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    public function previewInitialCharges(Enrollment $enrollment): array
    {
        $preview = [];
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
                'type'   => 'Student',
                'name'   => $definition->name,
                'amount' => $definition->default_amount,
            ];
        }

        $firstProgression = $enrollment->progressions()
            ->where('trimester_sequence', 1)
            ->first();

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
            // Skip if this charge already exists for T1 (avoids misleading preview)
            if ($firstProgression) {
                $exists = StudentFeeItem::query()
                    ->where('enrollment_id', $enrollment->id)
                    ->where('enrollment_progression_id', $firstProgression->id)
                    ->where('course_fee_plan_id', $plan->id)
                    ->exists();

                if ($exists) {
                    continue;
                }
            }

            $preview[] = [
                'type'   => 'Trimester',
                'name'   => $plan->feeDefinition?->name ?? 'Trimester',
                'amount' => $plan->amount,
            ];
        }

        return $preview;
    }
}
