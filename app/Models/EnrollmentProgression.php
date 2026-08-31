<?php

namespace App\Models;

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
}
