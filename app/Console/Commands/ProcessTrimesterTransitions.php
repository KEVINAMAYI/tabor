<?php

namespace App\Console\Commands;

use App\Models\Trimester;
use App\Models\EnrollmentProgression;
use App\Services\AcademicCalendarService;
use App\Services\FeeGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessTrimesterTransitions extends Command
{
    protected $signature = 'finance:process-trimester-transitions';
    protected $description = 'Close ended trimesters, activate current trimesters, and generate fees for active progressions';

    public function handle(): int
    {
        DB::transaction(function () {
            Trimester::query()
                ->whereDate('end_date', '<', now())
                ->where('status', '!=', 'closed')
                ->update(['status' => 'closed']);

            Trimester::query()
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->update(['status' => 'active']);

            Trimester::query()
                ->whereDate('start_date', '>', now())
                ->where('status', '!=', 'upcoming')
                ->update(['status' => 'upcoming']);

            $activeTrimester = Trimester::query()
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if (! $activeTrimester) {
                $activeTrimester = app(AcademicCalendarService::class)
                    ->getOrCreateTrimesterForDate(now());
            }

            EnrollmentProgression::query()
                ->where('trimester_id', $activeTrimester->id)
                ->whereIn('status', ['upcoming', 'active'])
                ->chunkById(100, function ($progressions) {
                    foreach ($progressions as $progression) {
                        $progression->update([
                            'status' => 'active',
                            'started_at' => $progression->started_at ?? $progression->trimester->start_date,
                        ]);

                        app(FeeGenerationService::class)
                            ->generateChargesForProgression($progression);
                    }
                });

            EnrollmentProgression::query()
                ->whereHas('trimester', fn ($q) => $q->whereDate('end_date', '<', now()))
                ->where('status', 'active')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now()->toDateString(),
                ]);
        });

        $this->info('Trimester transitions processed successfully.');

        return self::SUCCESS;
    }
}
