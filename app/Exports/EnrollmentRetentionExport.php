<?php

namespace App\Exports;

use App\Services\Reports\EnrollmentRetentionReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnrollmentRetentionExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    public function __construct(
        protected ?int $courseId = null,
        protected ?int $academicYearId = null
    ) {
    }

    public function view(): View
    {
        $service = app(EnrollmentRetentionReportService::class);

        return view('exports.enrollment-retention.index', [
            'summary' => $service->summary($this->courseId, $this->academicYearId),
            'trimesterBreakdown' => $service->trimesterBreakdown($this->courseId, $this->academicYearId),
            'courseBreakdown' => $service->courseBreakdown($this->academicYearId),
            'title' => 'Enrollment & Retention Report',
            'date' => now()->format('d M Y, H:i'),
            'isExcel' => true,
        ]);
    }

    public function title(): string
    {
        return 'Enrollment & Retention';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2c3e50']],
        ]);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle('A1:G' . $sheet->getHighestRow())->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
