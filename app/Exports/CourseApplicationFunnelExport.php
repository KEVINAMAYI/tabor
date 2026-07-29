<?php

namespace App\Exports;

use App\Services\Reports\CourseApplicationFunnelReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseApplicationFunnelExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    public function __construct(
        protected ?int $courseId = null,
        protected ?string $from = null,
        protected ?string $to = null,
        protected ?string $status = null,
        protected ?int $reviewerId = null
    ) {
    }

    public function view(): View
    {
        $service = app(CourseApplicationFunnelReportService::class);

        return view('exports.course-application-funnel.index', [
            'summary' => $service->summary($this->courseId, $this->from, $this->to, $this->reviewerId),
            'courseBreakdown' => $service->courseBreakdown($this->from, $this->to, $this->status),
            'reviewerBreakdown' => $service->reviewerBreakdown($this->courseId, $this->from, $this->to),
            'title' => 'Course-Application Funnel Report',
            'date' => now()->format('d M Y, H:i'),
            'isExcel' => true,
        ]);
    }

    public function title(): string
    {
        return 'Application Funnel';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2c3e50']],
        ]);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle('A1:E' . $sheet->getHighestRow())->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
