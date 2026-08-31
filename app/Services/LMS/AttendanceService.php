<?php

namespace App\Services\LMS;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\IntakeModule;
use App\Models\ModuleSession;
use Illuminate\Support\Collection;

class AttendanceService
{
    /**
     * Attendance stats for one student in one intake module.
     */
    public function statsForEnrollment(Enrollment $enrollment, IntakeModule $intakeModule): array
    {
        $sessionIds = ModuleSession::where('intake_module_id', $intakeModule->id)->pluck('id');

        if ($sessionIds->isEmpty()) {
            return $this->emptyStats();
        }

        $records = Attendance::where('enrollment_id', $enrollment->id)
            ->whereIn('module_session_id', $sessionIds)
            ->get();

        $total   = $sessionIds->count();
        $present = $records->where('status', 'present')->count();
        $late    = $records->where('status', 'late')->count();
        $absent  = $records->where('status', 'absent')->count();

        // Late counts as 0.5 for percentage purposes
        $attended   = $present + ($late * 0.5);
        $percentage = $total > 0 ? round(($attended / $total) * 100, 1) : 0;

        return [
            'total'      => $total,
            'present'    => $present,
            'late'       => $late,
            'absent'     => $absent,
            'percentage' => $percentage,
            'flag'       => $percentage < 75,  // flag if below 75%
        ];
    }

    /**
     * Attendance for all students in a session (for lecturer marking).
     */
    public function forSession(ModuleSession $session): Collection
    {
        $enrollments = Enrollment::forTrimester($session->intakeModule->trimester_id)
            ->with('student.user')
            ->get();

        $records = Attendance::where('module_session_id', $session->id)
            ->get()
            ->keyBy('enrollment_id');

        return $enrollments->map(fn($e) => [
            'enrollment' => $e,
            'student'    => $e->student,
            'record'     => $records->get($e->id),
            'status'     => $records->get($e->id)?->status ?? 'absent',
        ]);
    }

    /**
     * Summary for all students in one intake module (admin/lecturer report).
     */
    public function summaryForModule(IntakeModule $intakeModule): Collection
    {
        $sessions = ModuleSession::where('intake_module_id', $intakeModule->id)->get();

        if ($sessions->isEmpty()) {
            return collect();
        }

        $enrollments = Enrollment::forTrimester($intakeModule->trimester_id)
            ->whereHas('course.modules', fn($q) => $q->where('modules.id', $intakeModule->module_id))
            ->with('student.user')
            ->get();

        return $enrollments->map(function ($enrollment) use ($intakeModule) {
            return [
                'enrollment' => $enrollment,
                'student'    => $enrollment->student,
                ...$this->statsForEnrollment($enrollment, $intakeModule),
            ];
        });
    }

    private function emptyStats(): array
    {
        return [
            'total'      => 0,
            'present'    => 0,
            'late'       => 0,
            'absent'     => 0,
            'percentage' => 0,
            'flag'       => false,
        ];
    }
}
