<?php

namespace App\Services\Reports;

use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueReportService
{
    public function query(?string $from, ?string $to, ?int $courseId = null, ?string $method = null): Builder
    {
        return Payment::query()
            ->whereRaw("COALESCE(payments.method, payments.payment_method) != 'discount'")
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to))
            ->when($method, fn ($q) => $q->whereRaw('COALESCE(payments.method, payments.payment_method) = ?', [$method]))
            ->when($courseId, fn ($q) => $q->whereHas('enrollment', fn ($qq) => $qq->where('course_id', $courseId)));
    }

    public function methodOptions(): Collection
    {
        return Payment::query()
            ->selectRaw('COALESCE(method, payment_method) as method_name')
            ->whereRaw("COALESCE(method, payment_method) IS NOT NULL AND COALESCE(method, payment_method) != 'discount'")
            ->distinct()
            ->orderBy('method_name')
            ->pluck('method_name');
    }

    public function summary(?string $from, ?string $to, ?int $courseId = null, ?string $method = null): array
    {
        $totals = $this->query($from, $to, $courseId, $method)
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as txn_count')
            ->first();

        $total = (float) $totals->total;
        $count = (int) $totals->txn_count;

        return [
            'total_collected' => $total,
            'transaction_count' => $count,
            'average_payment' => $count ? $total / $count : 0.0,
        ];
    }

    public function monthlyTrend(?string $from, ?string $to, ?int $courseId = null, ?string $method = null): array
    {
        $fromDate = $from ? Carbon::parse($from) : now()->subMonths(11)->startOfMonth();
        $toDate = $to ? Carbon::parse($to) : now();

        $rows = $this->query($fromDate->toDateString(), $toDate->toDateString(), $courseId, $method)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($fromDate->copy()->startOfMonth(), '1 month', $toDate->copy()->startOfMonth()) as $month) {
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $data[] = (float) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function courseBreakdown(?string $from, ?string $to, ?string $method = null): Collection
    {
        return $this->query($from, $to, null, $method)
            ->join('enrollments', 'enrollments.id', '=', 'payments.enrollment_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->selectRaw('courses.id as course_id, MIN(courses.title) as course_title, SUM(payments.amount) as total, COUNT(*) as txn_count')
            ->groupBy('courses.id')
            ->orderByDesc('total')
            ->get();
    }

    public function methodBreakdown(?string $from, ?string $to, ?int $courseId = null): Collection
    {
        return $this->query($from, $to, $courseId, null)
            ->selectRaw('COALESCE(method, payment_method) as method_name, SUM(amount) as total, COUNT(*) as txn_count')
            ->groupBy('method_name')
            ->orderByDesc('total')
            ->get();
    }
}
