<?php

namespace App\Actions\Enrollment;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\TrimesterAssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateEnrollmentAction
{
    public function execute(Enrollment $enrollment, array $data): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $data) {

            $course = Course::findOrFail($data['course_id']);

            $assignment = app(TrimesterAssignmentService::class)
                ->assign(
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

            return $enrollment->fresh();
        });
    }
}
