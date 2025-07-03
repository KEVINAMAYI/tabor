<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price',          // tuition / fee
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    // All modules that belong to this course
    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    // Enrolments (one row per student per intake)
    public function enrolments()
    {
        return $this->hasMany(Enrolment::class);
    }

    /* -----------------------------------------------------------------
     |  Indirect / convenience relationships
     |------------------------------------------------------------------
     */

    // Distinct intakes where ANY module of this course is offered
    public function intakes()
    {
        return $this->hasManyThrough(
            Intake::class,
            IntakeModule::class,
            'module_id',          // FK on intake_modules
            'id',                 // PK on intakes
            'id',                 // PK on courses
            'intake_id'           // FK on intake_modules
        )
            ->whereHas('module.course', fn ($q) => $q->where('courses.id', $this->id))
            ->distinct();
    }

    // Students who have ever enrolled in this course
    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            Enrollment::class,
            'course_id',  // FK on enrolments
            'id',         // PK on students
            'id',         // PK on courses
            'student_id'  // FK on enrolments
        )->distinct();
    }

    // Optional: lecturers teaching any of its modules in any intake
    public function lecturers()
    {
        return $this->belongsToMany(
            Lecturer::class,
            'intake_module_lecturers',      // pivot table
            'course_id',                    // Needs extra column OR a sub‑query
            'lecturer_id'
        )->distinct();
    }
}
