<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AssessmentSubmission extends Model
{
    use HasFactory;

    /*--------------------------------------------------------------
    | Mass‑assignable columns
    |--------------------------------------------------------------*/
    protected $fillable = [
        'assessment_id',
        'enrollment_id',
        'file_path',
        'submitted_at',
        'mark',
        'feedback',
        'graded_at',
    ];

    /*--------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------*/
    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
        'mark'         => 'integer',
    ];

    /*--------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------*/
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /* Convenience hops */
    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            Enrollment::class,
            'id',          // PK on enrollments
            'id',          // PK on students
            'enrollment_id',
            'student_id'   // FK on enrollments
        );
    }

    public function module()
    {
        return $this->hasOneThrough(
            Module::class,
            Assessment::class,
            'id',          // PK on assessments
            'id',          // PK on modules
            'assessment_id',
            'module_id'
        );
    }

    public function intake()
    {
        return $this->hasOneThrough(
            Intake::class,
            Enrollment::class,
            'id',
            'id',
            'enrollment_id',
            'intake_id'
        );
    }

    /*--------------------------------------------------------------
    | Accessors / helpers
    |--------------------------------------------------------------*/
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    public function isGraded(): bool
    {
        return ! is_null($this->graded_at);
    }

    /*--------------------------------------------------------------
    | Query scopes
    |--------------------------------------------------------------*/
    public function scopeGraded($q)
    {
        return $q->whereNotNull('graded_at');
    }

    public function scopeUngraded($q)
    {
        return $q->whereNull('graded_at');
    }
}
