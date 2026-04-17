<?php

namespace App\Actions\Enrollment;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\TrimesterAssignmentService;
use App\Services\FeeGenerationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateEnrollmentAction
{
    public function execute(Student $student, array $data): Enrollment
    {
        DB::beginTransaction();

        try {
            $course = Course::query()->findOrFail((int) $data['course_id']);

            $assignment = app(TrimesterAssignmentService::class)->assign(
                Carbon::parse($data['admission_date']),
                $course
            );

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'admission_date' => $data['admission_date'],
                'status' => $data['status'],
                'intake_trimester_id' => $assignment['intake_trimester_id'],
                'assigned_start_trimester_id' => $assignment['assigned_start_trimester_id'],
            ]);

            app(FeeGenerationService::class)->generateInitialCharges($enrollment);

            DB::commit();

            return $enrollment;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to create enrollment', [
                'student_id' => $student->id,
                'payload' => $data,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}