<?php

namespace App\Actions\Enrollment;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentProgressionService;
use App\Services\FeeGenerationService;
use App\Services\TrimesterAssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateEnrollmentAction
{
    public function execute(Student $student, array $data): Enrollment
    {
        return DB::transaction(function () use ($student, $data) {

            $course = Course::findOrFail($data['course_id']);

            $assignment = app(TrimesterAssignmentService::class)
                ->assign(
                    Carbon::parse($data['admission_date']),
                    $course
                );

            if (
                empty($assignment['intake_trimester_id']) ||
                empty($assignment['assigned_start_trimester_id'])
            ) {
                throw new \RuntimeException(
                    'Unable to assign trimester. Check trimester setup.'
                );
            }

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'admission_date' => $data['admission_date'],
                'status' => $data['status'] ?? 'active',
                'intake_trimester_id' => $assignment['intake_trimester_id'],
                'assigned_start_trimester_id' => $assignment['assigned_start_trimester_id'],
            ]);

            app(EnrollmentProgressionService::class)
                ->generateForEnrollment($enrollment);

            app(FeeGenerationService::class)
                ->generateInitialCharges($enrollment);

            return $enrollment->fresh();
        });
    }
}
