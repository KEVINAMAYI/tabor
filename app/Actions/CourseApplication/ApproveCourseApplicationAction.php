<?php

namespace App\Actions\CourseApplication;

use App\Actions\Enrollment\CreateEnrollmentAction;
use App\Actions\Student\CreateStudentAccountAction;
use App\Models\CourseApplication;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class ApproveCourseApplicationAction
{
    public function execute(CourseApplication $application, array $data): Enrollment
    {
        if ($application->status !== 'pending') {
            throw new \RuntimeException('Only pending applications can be approved.');
        }

        return DB::transaction(function () use ($application, $data) {
            $student = app(CreateStudentAccountAction::class)->execute([
                'first_name' => $application->first_name,
                'last_name' => $application->last_name,
                'email' => $application->email,
                'phone_number' => $application->phone_number,
                'id_number' => $application->id_number,
                'date_of_birth' => $application->date_of_birth,
                'address' => $application->address,
                'country' => $application->country,
                'highest_level_of_education' => $application->highest_level_of_education,
                'id_url' => $application->id_url,
                'kcse_certificate' => $application->kcse_certificate,
                'passport_size_url' => $application->passport_size_url,
            ]);

            $enrollment = app(CreateEnrollmentAction::class)->execute($student, [
                'course_id' => $application->course_id,
                'admission_date' => $data['admission_date'],
                'status' => $data['enrollment_status'] ?? 'active',
                'fee_overrides' => $data['fee_overrides'] ?? null,
            ]);

            $application->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'promoted_student_id' => $student->id,
                'promoted_enrollment_id' => $enrollment->id,
            ]);

            return $enrollment;
        });
    }
}
