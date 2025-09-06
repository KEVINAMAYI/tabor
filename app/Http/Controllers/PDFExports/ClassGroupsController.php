<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\ClassGroupsReportService;
use App\Services\CourseReportService;
use App\Services\ReportGeneratorService;

class ClassGroupsController extends Controller
{
    protected ClassGroupsReportService $reportService;
    protected ReportGeneratorService $reportGenerator;

    public function __construct(ClassGroupsReportService $reportService, ReportGeneratorService $reportGenerator)
    {
        $this->reportService = $reportService;
        $this->reportGenerator = $reportGenerator;
    }

    public function exportClassGroupsPdf()
    {

        $classGroups = $this->reportService->getClassGroups();

        $pdf = $this->reportGenerator->generate(
            'exports.class-groups.index',
            [
                'classGroups' => $classGroups,
                'title' => 'Class Groups Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'class-groups-report',
        );

        return $pdf->download('class-groups-report.pdf');
    }
}
