<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    /**
     * Mass‑assignable columns.
     */
    protected $fillable = [
        'student_id',
        'course_id',
        'intake_id',
        'enrolled_at',   // datetime the student was registered
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function intake()
    {
        return $this->belongsTo(Intake::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /* -----------------------------------------------------------------
     |  Attendance & submissions
     |------------------------------------------------------------------
     */

    // Sessions the student attended (attendance pivot)
    public function sessions()
    {
        return $this->belongsToMany(
            ModuleSession::class,
            'attendance',             // pivot table
            'enrollment_id',
            'module_session_id'
        )
            ->withPivot('status')         // present / absent / late
            ->withTimestamps();
    }

    // Assessment submissions for this enrolment
    public function submissions()
    {
        return $this->hasMany(AssessmentSubmission::class);
    }

    /* -----------------------------------------------------------------
     |  Convenience helpers
     |------------------------------------------------------------------
     */

    /**
     * Is the enrolment fully paid?
     */
    public function isSettled(): bool
    {
        // assuming `price` lives on the course
        return $this->payments()->sum('amount') >= $this->course->price;
    }
}
