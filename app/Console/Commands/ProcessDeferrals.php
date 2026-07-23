<?php

namespace App\Console\Commands;

use App\Models\EnrollmentDeferral;
use App\Services\DeferralService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDeferrals extends Command
{
    protected $signature = 'enrollment:process-deferrals
        {--date= : Override process date (YYYY-MM-DD, defaults to today)}
        {--dry-run : Show what would be processed without making changes}';

    protected $description = 'Resume students from approved deferrals when their resume trimester has arrived';

    public function handle(DeferralService $deferralService): int
    {
        $processDate = $this->option('date')
            ? now()->parse($this->option('date'))
            : now();

        $this->info("Processing deferrals as of: {$processDate->toDateString()}");

        $deferrals = EnrollmentDeferral::query()
            ->with(['enrollment.course', 'resumeTrimester'])
            ->where('status', 'approved')
            ->whereNull('resumed_at')
            ->whereHas('resumeTrimester', function ($q) use ($processDate) {
                $q->whereDate('start_date', '<=', $processDate->toDateString());
            })
            ->get();

        if ($deferrals->isEmpty()) {
            $this->info('No deferrals ready to resume.');
            return self::SUCCESS;
        }

        $this->info("Found {$deferrals->count()} deferral(s) ready to resume.");

        if ($this->option('dry-run')) {
            foreach ($deferrals as $deferral) {
                $this->line(
                    "  [DRY-RUN] Enrollment #{$deferral->enrollment_id} — "
                    . "Student #{$deferral->enrollment?->student_id} — "
                    . "Resume trimester: {$deferral->resumeTrimester?->name}"
                );
            }
            return self::SUCCESS;
        }

        $resumed = 0;
        $failed = 0;

        foreach ($deferrals as $deferral) {
            try {
                $deferralService->resumeFromDeferral($deferral);

                $this->line(
                    "  Resumed enrollment #{$deferral->enrollment_id} "
                    . "(student #{$deferral->enrollment?->student_id})"
                );

                Log::info('Deferral resumed', [
                    'deferral_id'   => $deferral->id,
                    'enrollment_id' => $deferral->enrollment_id,
                ]);

                $resumed++;
            } catch (\Throwable $e) {
                $this->error(
                    "  Failed to resume deferral #{$deferral->id}: {$e->getMessage()}"
                );

                Log::error('Failed to resume deferral', [
                    'deferral_id' => $deferral->id,
                    'error'       => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        $this->info("Resumed: {$resumed}. Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
