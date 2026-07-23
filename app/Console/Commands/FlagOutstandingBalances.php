<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\StudentFeeItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlagOutstandingBalances extends Command
{
    protected $signature = 'finance:flag-outstanding-balances
        {--threshold=5000 : Minimum outstanding balance in KES to flag}
        {--dry-run : Report only, do not log to database}';

    protected $description = 'Identify students with outstanding balances above a threshold for follow-up';

    protected array $billableStatuses = [
        'active',
        'course_completed',
        'pending_graduation',
        'graduated',
    ];

    public function handle(): int
    {
        $threshold = (float) $this->option('threshold');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for outstanding balances above KES " . number_format($threshold, 2));

        $results = StudentFeeItem::query()
            ->select([
                'student_fee_items.student_id',
                'student_fee_items.enrollment_id',
                DB::raw('SUM(student_fee_items.balance) as total_outstanding'),
            ])
            ->join('enrollments', 'enrollments.id', '=', 'student_fee_items.enrollment_id')
            ->whereIn('enrollments.status', $this->billableStatuses)
            ->whereIn('student_fee_items.status', ['pending', 'partial'])
            ->where('student_fee_items.balance', '>', 0)
            ->groupBy('student_fee_items.student_id', 'student_fee_items.enrollment_id')
            ->having('total_outstanding', '>=', $threshold)
            ->orderByDesc('total_outstanding')
            ->get();

        if ($results->isEmpty()) {
            $this->info("No outstanding balances above threshold. All clear.");
            return self::SUCCESS;
        }

        $this->info("Found {$results->count()} enrollment(s) with outstanding balance ≥ KES " . number_format($threshold, 2));

        $headers = ['Student ID', 'Enrollment ID', 'Outstanding (KES)'];
        $rows = $results->map(fn($r) => [
            $r->student_id,
            $r->enrollment_id,
            number_format($r->total_outstanding, 2),
        ])->toArray();

        $this->table($headers, $rows);

        if (!$dryRun) {
            Log::info('Outstanding balance report', [
                'threshold'    => $threshold,
                'count'        => $results->count(),
                'total_at_risk' => $results->sum('total_outstanding'),
                'generated_at' => now()->toISOString(),
            ]);
        }

        return self::SUCCESS;
    }
}
