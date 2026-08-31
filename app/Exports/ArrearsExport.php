<?php

namespace App\Exports;

use App\Services\Reports\ArrearsReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArrearsExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    public function __construct(
        protected ?int $courseId = null,
        protected ?string $search = null,
        protected float $minBalance = 0
    ) {
    }

    public function view(): View
    {
        $service = app(ArrearsReportService::class);

        return view('exports.arrears.index', [
            'rows' => $service->query($this->courseId, $this->search, $this->minBalance)->get(),
            'summary' => $service->summary($this->courseId, $this->search, $this->minBalance),
            'title' => 'Outstanding Balances Report',
            'date' => now()->format('d M Y, H:i'),
            'isExcel' => true,
        ]);
    }

    public function title(): string
    {
        return 'Outstanding Balances';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2c3e50']],
        ]);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle('A1:H' . $sheet->getHighestRow())->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
