<?php

namespace App\Services\Reports;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Services\Finance\FinanceReconciliationService;
use Illuminate\Support\Collection;

class ReconciliationReportService
{
    public function __construct(protected FinanceReconciliationService $reconciliationService)
    {
    }

    protected function studentIds(): Collection
    {
        return Payment::query()->whereNotNull('student_id')->distinct()->pluck('student_id')
            ->merge(StudentFeeItem::query()->distinct()->pluck('student_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Runs detectMismatches() for every student with fee items/payments.
     * Synchronous, one pair of queries per student — fine at current scale
     * (a few hundred students); revisit with caching if that grows.
     */
    protected function affectedStudents(): Collection
    {
        return $this->studentIds()
            ->map(fn ($studentId) => [
                'student_id' => $studentId,
                'mismatches' => $this->reconciliationService->detectMismatches($studentId),
            ])
            ->filter(fn ($row) => $row['mismatches']->isNotEmpty())
            ->values();
    }

    public function summary(): array
    {
        $affected = $this->affectedStudents();

        $byType = $affected
            ->flatMap(fn ($row) => $row['mismatches'])
            ->groupBy('type')
            ->map(fn ($group) => $group->count());

        return [
            'students_checked' => $this->studentIds()->count(),
            'students_with_mismatches' => $affected->count(),
            'total_mismatches' => $affected->sum(fn ($row) => $row['mismatches']->count()),
            'by_type' => $byType,
        ];
    }

    public function studentBreakdown(): Collection
    {
        $affected = $this->affectedStudents();

        $students = Student::whereIn('id', $affected->pluck('student_id'))
            ->get(['id', 'first_name', 'last_name', 'admission_number'])
            ->keyBy('id');

        return $affected->map(function ($row) use ($students) {
            $student = $students->get($row['student_id']);

            return (object) [
                'student_id' => $row['student_id'],
                'student_name' => $student ? trim($student->first_name . ' ' . $student->last_name) : 'N/A',
                'admission_number' => $student->admission_number ?? 'N/A',
                'mismatch_count' => $row['mismatches']->count(),
                'types' => $row['mismatches']->pluck('type')->unique()->values()->implode(', '),
                'total_drift' => round((float) $row['mismatches']->sum('diff'), 2),
            ];
        })->sortByDesc('mismatch_count')->values();
    }
}
