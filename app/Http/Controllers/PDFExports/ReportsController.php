<?php

namespace App\Http\Controllers\PDFExports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ArrearsReportService;
use App\Services\Reports\CourseApplicationFunnelReportService;
use App\Services\Reports\EnrollmentRetentionReportService;
use App\Services\Reports\ReconciliationReportService;
use App\Services\Reports\RevenueReportService;
use App\Services\ReportGeneratorService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function __construct(protected ReportGeneratorService $reportGenerator)
    {
    }

    public function exportArrearsPdf(Request $request, ArrearsReportService $service)
    {
        $courseId = $request->integer('course') ?: null;
        $search = $request->string('search')->value() ?: null;
        $minBalance = (float) $request->input('min_balance', 0);

        $pdf = $this->reportGenerator->generate(
            'exports.arrears.index',
            [
                'rows' => $service->query($courseId, $search, $minBalance)->get(),
                'summary' => $service->summary($courseId, $search, $minBalance),
                'title' => 'Outstanding Balances Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'arrears-report',
        );

        return $pdf->download('arrears-report.pdf');
    }

    public function exportRevenuePdf(Request $request, RevenueReportService $service)
    {
        $from = $request->string('from')->value() ?: null;
        $to = $request->string('to')->value() ?: null;
        $courseId = $request->integer('course') ?: null;
        $method = $request->string('method')->value() ?: null;

        $rangeLabel = ($from || $to)
            ? trim(($from ?? 'earliest') . ' to ' . ($to ?? 'latest'))
            : 'Last 12 months';

        $pdf = $this->reportGenerator->generate(
            'exports.revenue.index',
            [
                'summary' => $service->summary($from, $to, $courseId, $method),
                'courseBreakdown' => $service->courseBreakdown($from, $to, $method),
                'methodBreakdown' => $service->methodBreakdown($from, $to, $courseId),
                'title' => 'Revenue & Collections Report',
                'rangeLabel' => $rangeLabel,
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'revenue-report',
        );

        return $pdf->download('revenue-report.pdf');
    }

    public function exportCourseApplicationFunnelPdf(Request $request, CourseApplicationFunnelReportService $service)
    {
        $courseId = $request->integer('course') ?: null;
        $from = $request->string('from')->value() ?: null;
        $to = $request->string('to')->value() ?: null;
        $status = $request->string('status')->value() ?: null;
        $reviewerId = $request->integer('reviewer') ?: null;

        $pdf = $this->reportGenerator->generate(
            'exports.course-application-funnel.index',
            [
                'summary' => $service->summary($courseId, $from, $to, $reviewerId),
                'courseBreakdown' => $service->courseBreakdown($from, $to, $status),
                'reviewerBreakdown' => $service->reviewerBreakdown($courseId, $from, $to),
                'title' => 'Course-Application Funnel Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'course-application-funnel-report',
        );

        return $pdf->download('course-application-funnel-report.pdf');
    }

    public function exportEnrollmentRetentionPdf(Request $request, EnrollmentRetentionReportService $service)
    {
        $courseId = $request->integer('course') ?: null;
        $academicYearId = $request->integer('academic_year') ?: null;

        $pdf = $this->reportGenerator->generate(
            'exports.enrollment-retention.index',
            [
                'summary' => $service->summary($courseId, $academicYearId),
                'trimesterBreakdown' => $service->trimesterBreakdown($courseId, $academicYearId),
                'courseBreakdown' => $service->courseBreakdown($academicYearId),
                'title' => 'Enrollment & Retention Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'enrollment-retention-report',
        );

        return $pdf->download('enrollment-retention-report.pdf');
    }

    public function exportReconciliationHealthPdf(ReconciliationReportService $service)
    {
        $pdf = $this->reportGenerator->generate(
            'exports.reconciliation-health.index',
            [
                'summary' => $service->summary(),
                'studentBreakdown' => $service->studentBreakdown(),
                'title' => 'Reconciliation Health Report',
                'date' => now()->format('d M Y, H:i'),
                'isExcel' => false,
            ],
            'reconciliation-health-report',
        );

        return $pdf->download('reconciliation-health-report.pdf');
    }
}
