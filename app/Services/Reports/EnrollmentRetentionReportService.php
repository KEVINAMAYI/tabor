<?php

namespace App\Services\Reports;

use App\Models\EnrollmentProgression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EnrollmentRetentionReportService
{
    protected array $retainedStatuses = ['active', 'completed'];

    /**
     * Only progressions whose trimester has actually started have a resolved
     * outcome. 'upcoming' progressions haven't happened yet, so they're
     * excluded everywhere here to keep retention rates meaningful.
     */
    protected array $resolvedStatuses = ['active', 'completed', 'deferred', 'repeated', 'cancelled'];

    public function query(?int $courseId = null, ?int $academicYearId = null): Builder
    {
        return EnrollmentProgression::query()
            ->join('enrollments', 'enrollments.id', '=', 'enrollment_progressions.enrollment_id')
            ->join('trimesters', 'trimesters.id', '=', 'enrollment_progressions.trimester_id')
            ->whereIn('enrollment_progressions.status', $this->resolvedStatuses)
            ->when($courseId, fn ($q) => $q->where('enrollments.course_id', $courseId))
            ->when($academicYearId, fn ($q) => $q->where('trimesters.academic_year_id', $academicYearId));
    }

    public function summary(?int $courseId = null, ?int $academicYearId = null): array
    {
        $rows = $this->query($courseId, $academicYearId)
            ->selectRaw('enrollment_progressions.status, COUNT(*) as total')
            ->groupBy('enrollment_progressions.status')
            ->pluck('total', 'status');

        $total = (int) $rows->sum();
        $retained = (int) $rows->only($this->retainedStatuses)->sum();

        return [
            'total_progressions' => $total,
            'retained' => $retained,
            'retention_rate' => $total ? round(($retained / $total) * 100, 1) : 0.0,
            'deferred' => (int) $rows->get('deferred', 0),
            'repeated' => (int) $rows->get('repeated', 0),
            'cancelled' => (int) $rows->get('cancelled', 0),
        ];
    }

    public function trimesterBreakdown(?int $courseId = null, ?int $academicYearId = null): Collection
    {
        return $this->query($courseId, $academicYearId)
            ->selectRaw('
                enrollment_progressions.trimester_sequence,
                COUNT(*) as total,
                SUM(CASE WHEN enrollment_progressions.status IN ("active","completed") THEN 1 ELSE 0 END) as retained,
                SUM(CASE WHEN enrollment_progressions.status = "deferred" THEN 1 ELSE 0 END) as deferred,
                SUM(CASE WHEN enrollment_progressions.status = "repeated" THEN 1 ELSE 0 END) as repeated,
                SUM(CASE WHEN enrollment_progressions.status = "cancelled" THEN 1 ELSE 0 END) as cancelled
            ')
            ->groupBy('enrollment_progressions.trimester_sequence')
            ->orderBy('enrollment_progressions.trimester_sequence')
            ->get()
            ->map(fn ($row) => (object) [
                'trimester_sequence' => $row->trimester_sequence,
                'total' => (int) $row->total,
                'retained' => (int) $row->retained,
                'deferred' => (int) $row->deferred,
                'repeated' => (int) $row->repeated,
                'cancelled' => (int) $row->cancelled,
                'retention_rate' => $row->total ? round(($row->retained / $row->total) * 100, 1) : 0.0,
            ]);
    }

    public function courseBreakdown(?int $academicYearId = null): Collection
    {
        return $this->query(null, $academicYearId)
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->selectRaw('
                enrollments.course_id,
                MIN(courses.title) as course_title,
                COUNT(*) as total,
                SUM(CASE WHEN enrollment_progressions.status IN ("active","completed") THEN 1 ELSE 0 END) as retained
            ')
            ->groupBy('enrollments.course_id')
            ->get()
            ->map(fn ($row) => (object) [
                'course_title' => $row->course_title,
                'total' => (int) $row->total,
                'retained' => (int) $row->retained,
                'retention_rate' => $row->total ? round(($row->retained / $row->total) * 100, 1) : 0.0,
            ])
            ->sortByDesc('retention_rate')
            ->values();
    }
}
