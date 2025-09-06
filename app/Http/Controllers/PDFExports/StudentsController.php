<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\StudentReportService;
use Illuminate\Http\Request;
use App\Services\ReportGeneratorService;

class StudentsController extends Controller
{
    protected StudentReportService $reportService;
    protected ReportGeneratorService $reportGenerator;

    public function __construct(StudentReportService $reportService, ReportGeneratorService $reportGenerator)
    {
        $this->reportService = $reportService;
        $this->reportGenerator = $reportGenerator;
    }

    public function exportStudentsPdf()
    {

        $students = $this->reportService->getStudents();

        $pdf = $this->reportGenerator->generate(
            'exports.students.index',
            [
                'students' => $students,
                'title' => 'Students Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'students-report',
        );

        return $pdf->download('students-report.pdf');
    }


    public function exportPendingEnrollmentsPdf()
    {

        $pending_enrollments = $this->reportService->getPendingEnrollments();

        $pdf = $this->reportGenerator->generate(
            'exports.students.pending-enrollments',
            [
                'enrollments' => $pending_enrollments,
                'title' => 'Pending Enrollments Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'students-report',
        );

        return $pdf->download('pending-enrollments.pdf');
    }

    public function exportEnrollmentsPdf()
    {

        $enrollments = $this->reportService->getEnrollments();

        $pdf = $this->reportGenerator->generate(
            'exports.students.enrollments',
            [
                'enrollments' => $enrollments,
                'title' => 'Enrollment Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'enrollment-report',
        );

        return $pdf->download('enrollment.pdf');
    }

}
