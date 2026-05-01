<?php

namespace App\Actions\Enrollment;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\TrimesterAssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateEnrollmentAction
{
    public function execute(Enrollment $enrollment, array $data): Enrollment
    {
        DB::beginTransaction();

        try {
            $course = Course::query()->findOrFail((int) $data['course_id']);

            $assignment = app(TrimesterAssignmentService::class)->assign(
                Carbon::parse($data['admission_date']),
                $course
            );

            $enrollment->update([
                'course_id' => $course->id,
                'admission_date' => $data['admission_date'],
                'status' => $data['status'],
                'intake_trimester_id' => $assignment['intake_trimester_id'],
                'assigned_start_trimester_id' => $assignment['assigned_start_trimester_id'],
            ]);

            DB::commit();

            return $enrollment->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to update enrollment', [
                'enrollment_id' => $enrollment->id,
                'payload' => $data,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}