<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\CourseReportService;
use App\Services\IntakeReportService;
use App\Services\ReportGeneratorService;

class IntakesController extends Controller
{
    protected IntakeReportService $reportService;
    protected ReportGeneratorService $reportGenerator;

    public function __construct(IntakeReportService $reportService, ReportGeneratorService $reportGenerator)
    {
        $this->reportService = $reportService;
        $this->reportGenerator = $reportGenerator;
    }

    public function exportIntakesPdf()
    {

        $intakes = $this->reportService->getIntakes();

        $pdf = $this->reportGenerator->generate(
            'exports.intakes.index',
            [
                'intakes' => $intakes,
                'title' => 'Intakes Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'intakes-report',
        );

        return $pdf->download('intakes-report.pdf');
    }
}
