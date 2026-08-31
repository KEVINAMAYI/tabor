<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\Accounting\BudgetReportService;
use App\Services\Accounting\TrialBalanceReportService;
use App\Services\ReportGeneratorService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(protected ReportGeneratorService $reportGenerator)
    {
    }

    public function exportTrialBalancePdf(Request $request, TrialBalanceReportService $service)
    {
        $accountingPeriodId = $request->integer('accounting_period_id') ?: null;
        $asOfDate = $request->string('as_of')->value() ?: null;

        $result = $service->generate($accountingPeriodId, $accountingPeriodId ? null : $asOfDate);

        $pdf = $this->reportGenerator->generate(
            'exports.trial-balance.index',
            [
                'rows' => $result['rows'],
                'totals' => $result['totals'],
                'title' => 'Trial Balance',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'trial-balance',
        );

        return $pdf->download('trial-balance.pdf');
    }

    public function exportBudgetReportPdf(Request $request, BudgetReportService $service)
    {
        $result = $service->generate($request->integer('financial_year_id'));

        $pdf = $this->reportGenerator->generate(
            'exports.budget-report.index',
            [
                'financialYear' => $result['financialYear'],
                'rows' => $result['rows'],
                'totals' => $result['totals'],
                'title' => 'Budget vs Actual — ' . $result['financialYear']->name,
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'budget-report',
        );

        return $pdf->download('budget-report.pdf');
    }
}
