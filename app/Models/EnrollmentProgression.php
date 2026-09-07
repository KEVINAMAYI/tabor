<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class EnrollmentProgression extends Model
{

protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function trimester()
    {
        return $this->belongsTo(Trimester::class);
    }

    public function feeItems()
    {
        return $this->hasMany(StudentFeeItem::class, 'enrollment_progression_id');
    }

    public function deferral()
    {
        return $this->hasOne(EnrollmentDeferral::class);
    }

    public function isDeferred(): bool
    {
        return $this->status === 'deferred';
    }

    public function isRepeated(): bool
    {
        return $this->status === 'repeated';
    }

    /**
     * Human label combining the progression sequence with the calendar
     * month/year it actually ran in, e.g. "T1 - September 2025".
     */
    public function getDisplayLabelAttribute(): string
    {
        $period = $this->trimester?->start_date?->format('F Y');

        return 'T' . $this->trimester_sequence . ($period ? " - {$period}" : '');
    }

    /**
     * The real [start, end] window this progression runs for.
     *
     * Single source of truth shared by StudentLedgerService (statement
     * periods) and ProcessTrimesterTransitions (rollover/completion) — they
     * must never compute this independently, or they silently drift apart
     * exactly like they did before: a continuous-intake course (e.g. German)
     * doesn't follow its linked trimester's calendar at all, its real end
     * date is started_at + the course's own duration. A German progression
     * attached to a trimester that already closed is often still genuinely
     * running for weeks afterward.
     */
    public function computedDateRange(): array
    {
        $this->loadMissing(['trimester', 'enrollment.course']);

        $course = $this->enrollment?->course;

        if ((bool) $course?->allows_continuous_intake) {
            $startDate = Carbon::parse(
                $this->started_at
                ?? $this->enrollment?->admission_date
                ?? $this->trimester?->start_date
                ?? now()
            )->startOfDay();

            // German-coded courses run 2 months from their own start date,
            // regardless of the trimester they happen to be attached to.
            // Other continuous-intake courses (Barista, ICT Certificate)
            // keep the existing 3-month default.
            $germanCodes = ['GLA1', 'GLA2', 'GLB1', 'GLB2', 'GRBC'];
            $durationMonths = in_array(strtoupper(trim($course->code ?? '')), $germanCodes, true) ? 2 : 3;

            $endDate = $startDate->copy()->addMonths($durationMonths)->subDay()->endOfDay();

            return [$startDate, $endDate];
        }

        $startDate = Carbon::parse(
            $this->started_at ?? $this->trimester?->start_date ?? now()
        )->startOfDay();

        $endDate = Carbon::parse(
            $this->completed_at ?? $this->trimester?->end_date ?? now()
        )->endOfDay();

        return [$startDate, $endDate];
    }

    public function computedEndDate(): Carbon
    {
        return $this->computedDateRange()[1];
    }
}
