<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Student;

class StudentReportService
{
    public function getStudents()
    {
        return Student::with('user')
            ->whereHas('user', function ($query) {
                $query->where('active', true);
            })
            ->orderBy('admission_number', 'asc')
            ->get();
    }

    public function getPendingEnrollments()
    {
        return Enrollment::whereIn('status', ['pending', 'rejected'])
            ->with(['student.user', 'course', 'intake'])
            ->get();
    }


    public function getEnrollments()
    {
        return Enrollment::whereIn('status', ['active'])
            ->with(['student.user', 'course', 'intake','assignedStartTrimester','studentFeeItems'])
            ->get();
    }

}
