<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\LecturerReportService;
use App\Services\ReportGeneratorService;
use App\Services\StudentReportService;
use Illuminate\Http\Request;

class LecturersController extends Controller
{
    protected LecturerReportService $reportService;
    protected ReportGeneratorService $reportGenerator;

    public function __construct(LecturerReportService $reportService, ReportGeneratorService $reportGenerator)
    {
        $this->reportService = $reportService;
        $this->reportGenerator = $reportGenerator;
    }

    public function exportLecturersPdf()
    {

        $lecturers = $this->reportService->getLecturers();

        $pdf = $this->reportGenerator->generate(
            'exports.lecturers.index',
            [
                'lecturers' => $lecturers,
                'title' => 'Lecturers Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'lecturers-report',
        );

        return $pdf->download('lecturers-report.pdf');
    }
}
