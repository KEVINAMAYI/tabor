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
    protected $guarded = ['id'];

    protected $casts = [
    'include_registration_fee' => 'boolean',
    'include_student_id_fee' => 'boolean',
    'include_stationery_fee' => 'boolean',
    'include_caution_fee' => 'boolean',
    'admission_date' => 'date',
];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

     public function trimesters()
     {
         return $this->hasMany(EnrollmentTrimester::class);
     }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function intake()  // to delete after replacing code with the below two trimester relationships
    {
        return $this->belongsTo(Intake::class);
    }

    public function intakeTrimester()
    {
        return $this->belongsTo(Trimester::class, 'intake_trimester_id');
    }

    public function assignedStartTrimester()
    {
        return $this->belongsTo(Trimester::class, 'assigned_start_trimester_id');
    }

    public function feeItems()
{
    return $this->hasMany(StudentFeeItem::class);
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
