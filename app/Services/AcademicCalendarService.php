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

    /**
     * @deprecated Hardcodes calendar-quarter boundaries (Jan-Apr/May-Aug/
     * Sep-Dec), which won't match an institution's actual custom trimester
     * dates set via the Academic Calendar admin UI. Kept only because
     * getOrCreateTrimesterForDate()'s reactive same-day fallback still needs
     * *some* answer when nothing else exists. Do not call this to proactively
     * create the next trimester — use ensureNextTrimesterWithinLeadTime(),
     * which derives dates from the reference trimester's own duration.
     */
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

    /**
     * Proactively creates the trimester following $reference once we're
     * within $leadDays of $reference's end_date, so students/statements have
     * a real trimester to roll into ahead of time instead of only ever
     * getting one reactively on the day it's needed. Unlike
     * getOrCreateNextTrimester(), dates are derived from $reference's own
     * start/end (same duration, starting the day after $reference ends) —
     * not hardcoded calendar quarters — so it respects whatever custom
     * academic calendar the institution actually uses.
     *
     * Returns null if it's not yet within the lead window, or a trimester
     * already exists starting on/after $reference's end date.
     */
    public function ensureNextTrimesterWithinLeadTime(Trimester $reference, int $leadDays, ?Carbon $today = null): ?Trimester
    {
        $today = $today ?? now();

        if ($today->lt(Carbon::parse($reference->end_date)->subDays($leadDays))) {
            return null;
        }

        $alreadyExists = Trimester::query()
            ->where('start_date', '>', $reference->end_date)
            ->exists();

        if ($alreadyExists) {
            return null;
        }

        $nextStart = Carbon::parse($reference->end_date)->addDay();
        $durationDays = Carbon::parse($reference->start_date)->diffInDays(Carbon::parse($reference->end_date));
        $nextEnd = $nextStart->copy()->addDays($durationDays);
        $midpoint = $nextStart->copy()->addDays(intdiv($durationDays, 2));

        $nextYear = (int) $nextStart->format('Y');
        $nextNumber = $reference->trimester_number >= 3 ? 1 : $reference->trimester_number + 1;

        $academicYear = AcademicYear::firstOrCreate(
            ['name' => (string) $nextYear],
            [
                'start_date' => "{$nextYear}-01-01",
                'end_date' => "{$nextYear}-12-31",
                'active' => false,
            ]
        );

        return Trimester::firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'trimester_number' => $nextNumber,
            ],
            [
                'name' => "Trimester {$nextNumber}",
                'start_date' => $nextStart->toDateString(),
                'end_date' => $nextEnd->toDateString(),
                'midpoint_date' => $midpoint->toDateString(),
                'intake_cutoff_date' => $midpoint->toDateString(),
                'status' => 'upcoming',
            ]
        );
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
