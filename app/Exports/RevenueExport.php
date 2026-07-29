<?php

namespace App\Exports;

use App\Services\Reports\RevenueReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RevenueExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    public function __construct(
        protected ?string $from = null,
        protected ?string $to = null,
        protected ?int $courseId = null,
        protected ?string $method = null
    ) {
    }

    public function view(): View
    {
        $service = app(RevenueReportService::class);

        return view('exports.revenue.index', [
            'summary' => $service->summary($this->from, $this->to, $this->courseId, $this->method),
            'courseBreakdown' => $service->courseBreakdown($this->from, $this->to, $this->method),
            'methodBreakdown' => $service->methodBreakdown($this->from, $this->to, $this->courseId),
            'title' => 'Revenue & Collections Report',
            'rangeLabel' => $this->rangeLabel(),
            'date' => now()->format('d M Y, H:i'),
            'isExcel' => true,
        ]);
    }

    public function title(): string
    {
        return 'Revenue & Collections';
    }

    protected function rangeLabel(): string
    {
        if (!$this->from && !$this->to) {
            return 'Last 12 months';
        }

        return trim(($this->from ?? 'earliest') . ' to ' . ($this->to ?? 'latest'));
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2c3e50']],
        ]);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle('A1:C' . $sheet->getHighestRow())->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
