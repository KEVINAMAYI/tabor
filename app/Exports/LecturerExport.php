<?php

namespace App\Exports;

use App\Services\LecturerReportService;
use App\Services\ReportGeneratorService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LecturerExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{

    protected LecturerReportService $reportService;
    protected ReportGeneratorService $reportGenerator;

    public function __construct(LecturerReportService $reportService, ReportGeneratorService $reportGenerator)
    {
        $this->reportService = $reportService;
        $this->reportGenerator = $reportGenerator;
    }


    public function view(): View
    {

        $lecturers = $this->reportService->getLecturers();

        return view('exports.lecturers.index', [
            'lecturers' => $lecturers,
            'title' => 'Lecturers Report',
            'date' => now()->format('d M Y, H:i'),
            'isExcel' => true
        ]);
    }


    public function title(): string
    {
        return 'Lecturers Report';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2c3e50'],
            ],
        ]);


        $sheet->getStyle('A2:R2')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2c3e50'],
            ],
        ]);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $sheet->getStyle('A1:G' . $sheet->getHighestRow())->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
