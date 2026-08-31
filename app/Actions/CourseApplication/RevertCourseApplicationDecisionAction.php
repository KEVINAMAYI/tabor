<?php

namespace App\Actions\CourseApplication;

use App\Models\CourseApplication;
use App\Models\Enrollment;
use App\Models\EnrollmentProgression;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevertCourseApplicationDecisionAction
{
    /**
     * Undo a previous approve/reject decision, putting the application back to
     * pending. For a rejected application this is a simple field reset. For an
     * approved one, this also unwinds the Student/User/Enrollment/fee items that
     * were created — but only if nothing real has happened since (payments, other
     * enrollments, trimester progress). If any of that guard trips, we refuse
     * rather than guess, and the admin has to unwind it manually.
     */
    public function execute(CourseApplication $application): void
    {
        if (!in_array($application->status, ['approved', 'rejected'], true)) {
            throw new \RuntimeException('Only approved or rejected applications can be reverted.');
        }

        DB::transaction(function () use ($application) {
            if ($application->status === 'approved') {
                $this->revertApproval($application);
            }

            $application->update([
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'promoted_student_id' => null,
                'promoted_enrollment_id' => null,
            ]);
        });
    }

    protected function revertApproval(CourseApplication $application): void
    {
        $enrollment = $application->promoted_enrollment_id
            ? Enrollment::find($application->promoted_enrollment_id)
            : null;

        $student = $application->promoted_student_id
            ? Student::find($application->promoted_student_id)
            : null;

        if (!$enrollment && !$student) {
            // Whatever was created is already gone; nothing left to unwind.
            return;
        }

        if ($student) {
            $hasPayments = Payment::where('student_id', $student->id)->exists();

            if ($hasPayments) {
                throw new \RuntimeException('This student already has payments recorded — revert manually if you\'re sure.');
            }

            $enrollmentCount = Enrollment::where('student_id', $student->id)->count();

            if ($enrollmentCount > 1) {
                throw new \RuntimeException('This student has additional enrollments beyond the one created by this approval.');
            }
        }

        if ($enrollment) {
            if ($enrollment->progressions()->count() > 1) {
                throw new \RuntimeException('This enrollment has already progressed beyond its first trimester.');
            }

            StudentFeeItem::where('enrollment_id', $enrollment->id)->delete();
            EnrollmentProgression::where('enrollment_id', $enrollment->id)->delete();
            $enrollment->delete();
        }

        if ($student) {
            $userId = $student->user_id;
            $student->delete();

            if ($userId) {
                User::where('id', $userId)->delete();
            }
        }
    }
}
