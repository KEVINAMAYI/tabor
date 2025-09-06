<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\CourseReportService;
use App\Services\ReportGeneratorService;

class CoursesController extends Controller
{
    protected CourseReportService $reportService;
    protected ReportGeneratorService $reportGenerator;

    public function __construct(CourseReportService $reportService, ReportGeneratorService $reportGenerator)
    {
        $this->reportService = $reportService;
        $this->reportGenerator = $reportGenerator;
    }

    public function exportCoursesPdf()
    {

        $courses = $this->reportService->getCourses();

        $pdf = $this->reportGenerator->generate(
            'exports.courses.index',
            [
                'courses' => $courses,
                'title' => 'Courses Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'courses-report',
        );

        return $pdf->download('courses-report.pdf');
    }
}
