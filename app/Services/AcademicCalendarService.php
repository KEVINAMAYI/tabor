<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Trimester;
use Carbon\Carbon;

class AcademicCalendarService
{
    public function getOrCreateTrimesterForDate(Carbon|string $date): Trimester
    {
        $date = Carbon::parse($date);
        $year = (int) $date->format('Y');

        $academicYear = AcademicYear::firstOrCreate(
            ['name' => (string) $year],
            [
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'is_active' => false,
            ]
        );

        [$number, $start, $end, $midpoint] = $this->trimesterPeriodForDate($date);

        return Trimester::firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'trimester_number' => $number,
            ],
            [
                'name' => "Trimester {$number}",
                'start_date' => $start,
                'end_date' => $end,
                'midpoint_date' => $midpoint,
                'intake_cutoff_date' => $midpoint,
                'status' => $this->resolveStatus($start, $end),
            ]
        );
    }

    public function getOrCreateNextTrimester(Trimester $trimester): Trimester
    {
        $year = (int) $trimester->academicYear->name;
        $nextNumber = $trimester->trimester_number + 1;

        if ($nextNumber > 3) {
            $year++;
            $nextNumber = 1;
        }

        return $this->getOrCreateTrimesterByYearAndNumber($year, $nextNumber);
    }

    public function getOrCreateTrimesterByYearAndNumber(int $year, int $number): Trimester
    {
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => (string) $year],
            [
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'is_active' => false,
            ]
        );

        [$start, $end, $midpoint] = match ($number) {
            1 => ["{$year}-01-01", "{$year}-04-30", "{$year}-03-01"],
            2 => ["{$year}-05-01", "{$year}-08-31", "{$year}-07-01"],
            3 => ["{$year}-09-01", "{$year}-12-31", "{$year}-11-01"],
        };

        return Trimester::firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'trimester_number' => $number,
            ],
            [
                'name' => "Trimester {$number}",
                'start_date' => $start,
                'end_date' => $end,
                'midpoint_date' => $midpoint,
                'intake_cutoff_date' => $midpoint,
                'status' => $this->resolveStatus($start, $end),
            ]
        );
    }

    protected function trimesterPeriodForDate(Carbon $date): array
    {
        $year = (int) $date->format('Y');

        if ($date->between(Carbon::parse("{$year}-01-01"), Carbon::parse("{$year}-04-30"))) {
            return [1, "{$year}-01-01", "{$year}-04-30", "{$year}-03-01"];
        }

        if ($date->between(Carbon::parse("{$year}-05-01"), Carbon::parse("{$year}-08-31"))) {
            return [2, "{$year}-05-01", "{$year}-08-31", "{$year}-07-01"];
        }

        return [3, "{$year}-09-01", "{$year}-12-31", "{$year}-11-01"];
    }

    protected function resolveStatus(string $start, string $end): string
    {
        $today = now();

        if ($today->lt(Carbon::parse($start))) {
            return 'upcoming';
        }

        if ($today->between(Carbon::parse($start), Carbon::parse($end))) {
            return 'active';
        }

        return 'closed';
    }
}
