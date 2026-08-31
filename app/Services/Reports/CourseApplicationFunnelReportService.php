<?php

namespace App\Services\Reports;

use App\Models\CourseApplication;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourseApplicationFunnelReportService
{
    public function query(
        ?int $courseId = null,
        ?string $from = null,
        ?string $to = null,
        ?string $status = null,
        ?int $reviewerId = null
    ): Builder {
        return CourseApplication::query()
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($reviewerId, fn ($q) => $q->where('reviewed_by', $reviewerId))
            ->latest('created_at');
    }

    public function summary(?int $courseId = null, ?string $from = null, ?string $to = null, ?int $reviewerId = null): array
    {
        $submitted = $this->query($courseId, $from, $to, null, $reviewerId)->count();
        $approved = $this->query($courseId, $from, $to, 'approved', $reviewerId)->count();
        $rejected = $this->query($courseId, $from, $to, 'rejected', $reviewerId)->count();
        $pending = $this->query($courseId, $from, $to, 'pending', $reviewerId)->count();

        $avgTurnaroundHours = $this->query($courseId, $from, $to, null, $reviewerId)
            ->whereNotNull('reviewed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'submitted' => $submitted,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'conversion_rate' => $submitted ? round(($approved / $submitted) * 100, 1) : 0.0,
            'avg_turnaround_hours' => round((float) ($avgTurnaroundHours ?? 0), 1),
        ];
    }

    public function monthlyTrend(
        ?int $courseId = null,
        ?string $from = null,
        ?string $to = null,
        ?string $status = null,
        ?int $reviewerId = null
    ): array {
        $fromDate = $from ? Carbon::parse($from) : now()->subMonths(11)->startOfMonth();
        $toDate = $to ? Carbon::parse($to) : now();

        $rows = $this->query($courseId, $fromDate->toDateString(), $toDate->toDateString(), $status, $reviewerId)
            ->reorder()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($fromDate->copy()->startOfMonth(), '1 month', $toDate->copy()->startOfMonth()) as $month) {
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function reviewerBreakdown(?int $courseId = null, ?string $from = null, ?string $to = null): Collection
    {
        return $this->query($courseId, $from, $to, null, null)
            ->reorder()
            ->whereNotNull('reviewed_by')
            ->join('users', 'users.id', '=', 'course_applications.reviewed_by')
            ->selectRaw('
                course_applications.reviewed_by,
                MIN(users.name) as reviewer_name,
                COUNT(*) as reviewed_count,
                SUM(CASE WHEN course_applications.status = "approved" THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN course_applications.status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
                AVG(TIMESTAMPDIFF(HOUR, course_applications.created_at, course_applications.reviewed_at)) as avg_hours
            ')
            ->groupBy('course_applications.reviewed_by')
            ->orderByDesc('reviewed_count')
            ->get();
    }

    public function courseBreakdown(?string $from = null, ?string $to = null, ?string $status = null): Collection
    {
        return $this->query(null, $from, $to, $status, null)
            ->reorder()
            ->join('courses', 'courses.id', '=', 'course_applications.course_id')
            ->selectRaw('
                course_applications.course_id,
                MIN(courses.title) as course_title,
                COUNT(*) as submitted,
                SUM(CASE WHEN course_applications.status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN course_applications.status = "rejected" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN course_applications.status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->groupBy('course_applications.course_id')
            ->orderByDesc('submitted')
            ->get();
    }
}
