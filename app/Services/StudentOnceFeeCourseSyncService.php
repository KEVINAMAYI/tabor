<?php

namespace App\Services;

use App\Models\Course;
use App\Models\FeeDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentOnceFeeCourseSyncService
{
    public function syncForFeeDefinition(FeeDefinition $feeDefinition): void
    {
        if (!$this->isStudentOnceFee($feeDefinition)) {
            return;
        }

        $this->addFeeToNonContinuousCourses($feeDefinition);
    }

    public function removeForFeeDefinition(FeeDefinition $feeDefinition): void
    {
        if (!Schema::hasColumn('courses', 'chargeable_student_once_fee_definition_ids')) {
            return;
        }

        DB::transaction(function () use ($feeDefinition) {
            Course::query()
                ->where(function ($q) {
                    $q->where('allows_continuous_intake', false)
                        ->orWhereNull('allows_continuous_intake');
                })
                ->chunkById(100, function ($courses) use ($feeDefinition) {
                    foreach ($courses as $course) {
                        $ids = $course->chargeable_student_once_fee_definition_ids ?? [];

                        $ids = collect($ids)
                            ->map(fn($id) => (string) $id)
                            ->reject(fn($id) => $id === (string) $feeDefinition->id)
                            ->values()
                            ->all();

                        $course->forceFill([
                            'chargeable_student_once_fee_definition_ids' => $ids,
                        ])->save();
                    }
                });
        });
    }

    protected function addFeeToNonContinuousCourses(FeeDefinition $feeDefinition): void
    {
        if (!Schema::hasColumn('courses', 'chargeable_student_once_fee_definition_ids')) {
            return;
        }

        DB::transaction(function () use ($feeDefinition) {
            Course::query()
                ->where(function ($q) {
                    $q->where('allows_continuous_intake', false)
                        ->orWhereNull('allows_continuous_intake');
                })
                ->chunkById(100, function ($courses) use ($feeDefinition) {
                    foreach ($courses as $course) {
                        $ids = $course->chargeable_student_once_fee_definition_ids ?? [];

                        $ids = collect($ids)
                            ->map(fn($id) => (string) $id)
                            ->push((string) $feeDefinition->id)
                            ->unique()
                            ->values()
                            ->all();

                        $course->forceFill([
                            'chargeable_student_once_fee_definition_ids' => $ids,
                        ])->save();
                    }
                });
        });
    }

    protected function isStudentOnceFee(FeeDefinition $feeDefinition): bool
    {
        return $feeDefinition->scope === 'student'
            && (bool) $feeDefinition->applies_once === true
            && (bool) $feeDefinition->active === true;
    }
}
