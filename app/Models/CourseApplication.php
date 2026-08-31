<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseApplication extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function preferredTrimester(): BelongsTo
    {
        return $this->belongsTo(Trimester::class, 'preferred_trimester_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'promoted_student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'promoted_enrollment_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
