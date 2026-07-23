<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\PaymentsReportService;
use App\Services\ReportGeneratorService;

class PaymentsController extends Controller
{
    protected PaymentsReportService $reportService;
    protected ReportGeneratorService $reportGenerator;

    public function __construct(PaymentsReportService $reportService, ReportGeneratorService $reportGenerator)
    {
        $this->reportService = $reportService;
        $this->reportGenerator = $reportGenerator;
    }

    public function exportPaymentsPdf()
    {

        $payments = $this->reportService->getPayments();

        $pdf = $this->reportGenerator->generate(
            'exports.payments.index',
            [
                'payments' => $payments,
                'title' => 'Payments Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'payments-report',
        );

        return $pdf->download('payments-report.pdf');
    }
}
