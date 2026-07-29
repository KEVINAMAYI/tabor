<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ArrearsReportService
{
    protected array $billableStatuses = [
        'active',
        'course_completed',
        'pending_graduation',
        'graduated',
    ];

    protected function baseQuery(?int $courseId, ?string $search, float $minBalance)
    {
        return DB::table('student_fee_items')
            ->join('enrollments', 'enrollments.id', '=', 'student_fee_items.enrollment_id')
            ->join('students', 'students.id', '=', 'student_fee_items.student_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->whereIn('enrollments.status', $this->billableStatuses)
            ->whereIn('student_fee_items.status', ['pending', 'partial'])
            ->where('student_fee_items.balance', '>', 0)
            ->when($courseId, fn ($q) => $q->where('enrollments.course_id', $courseId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('students.first_name', 'like', "%{$search}%")
                        ->orWhere('students.last_name', 'like', "%{$search}%")
                        ->orWhere('students.admission_number', 'like', "%{$search}%");
                });
            })
            ->groupBy('student_fee_items.student_id', 'student_fee_items.enrollment_id')
            ->havingRaw('SUM(student_fee_items.balance) >= ?', [$minBalance]);
    }

    public function query(?int $courseId = null, ?string $search = null, float $minBalance = 0)
    {
        return $this->baseQuery($courseId, $search, $minBalance)
            ->select([
                'student_fee_items.student_id',
                'student_fee_items.enrollment_id',
                DB::raw('MIN(students.first_name) as first_name'),
                DB::raw('MIN(students.last_name) as last_name'),
                DB::raw('MIN(students.admission_number) as admission_number'),
                DB::raw('MIN(students.phone) as phone'),
                DB::raw('MIN(students.email) as email'),
                DB::raw('MIN(courses.title) as course_title'),
                DB::raw('MIN(enrollments.status) as enrollment_status'),
                DB::raw('SUM(student_fee_items.balance) as total_outstanding'),
                DB::raw('MIN(student_fee_items.due_date) as oldest_due_date'),
                DB::raw('COUNT(*) as items_count'),
            ])
            ->orderByDesc('total_outstanding');
    }

    public function summary(?int $courseId = null, ?string $search = null, float $minBalance = 0): array
    {
        $rows = $this->baseQuery($courseId, $search, $minBalance)
            ->select([
                'student_fee_items.student_id',
                DB::raw('SUM(student_fee_items.balance) as total_outstanding'),
            ])
            ->get();

        $count = $rows->count();

        return [
            'total_outstanding' => (float) $rows->sum('total_outstanding'),
            'affected_enrollments' => $count,
            'affected_students' => $rows->pluck('student_id')->unique()->count(),
            'average_outstanding' => $count ? (float) $rows->sum('total_outstanding') / $count : 0.0,
        ];
    }

    public function courseBreakdown(?string $search = null, float $minBalance = 0): Collection
    {
        return $this->baseQuery(null, $search, $minBalance)
            ->select([
                'enrollments.course_id',
                DB::raw('MIN(courses.title) as course_title'),
                DB::raw('SUM(student_fee_items.balance) as total_outstanding'),
            ])
            ->get()
            ->groupBy('course_id')
            ->map(fn ($group) => [
                'course_title' => $group->first()->course_title,
                'total_outstanding' => (float) $group->sum('total_outstanding'),
                'count' => $group->count(),
            ])
            ->sortByDesc('total_outstanding')
            ->values();
    }
}
