<?php

namespace App\Exports;

use App\Services\Reports\ReconciliationReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReconciliationHealthExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    public function view(): View
    {
        $service = app(ReconciliationReportService::class);

        return view('exports.reconciliation-health.index', [
            'summary' => $service->summary(),
            'studentBreakdown' => $service->studentBreakdown(),
            'title' => 'Reconciliation Health Report',
            'date' => now()->format('d M Y, H:i'),
            'isExcel' => true,
        ]);
    }

    public function title(): string
    {
        return 'Reconciliation Health';
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
