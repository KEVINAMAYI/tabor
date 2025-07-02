<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'user_id',
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    // linked to a user (for login credentials / portal access)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // every intake‑course the student is enrolled in
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    // tuition payments hang off each enrolment
    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Enrollment::class,   // through
            'student_id',       // FK on Enrolment
            'enrollment_id'      // FK on Payment
        );
    }

    /* -----------------------------------------------------------------
     |  Convenient “through” relationships
     |------------------------------------------------------------------
     */

    // quick access to all courses (distinct) the student has ever taken
    public function courses()
    {
        return $this->hasManyThrough(
            Course::class,
            Enrollment::class,
            'student_id',   // FK on Enrolment
            'id',           // PK on Course
            'id',           // PK on Student
            'course_id'     // FK on Enrolment
        )->distinct();
    }

    // all intakes the student has participated in
    public function intakes()
    {
        return $this->hasManyThrough(
            Intake::class,
            Enrollment::class,
            'student_id',
            'id',
            'id',
            'intake_id'
        )->distinct();
    }

    // attendance records via enrolments → sessions → attendance pivot
    public function sessions()
    {
        return $this->belongsToMany(
            ModuleSession::class,
            'attendances',
            'enrollment_id',
            'module_session_id'
        )
            ->withPivot('status')
            ->withTimestamps();
    }
}

