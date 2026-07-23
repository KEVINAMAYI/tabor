<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentDeferral extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function progression()
    {
        return $this->belongsTo(EnrollmentProgression::class, 'enrollment_progression_id');
    }

    public function resumeTrimester()
    {
        return $this->belongsTo(Trimester::class, 'resume_trimester_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
