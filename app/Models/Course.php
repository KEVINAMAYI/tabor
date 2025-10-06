<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'description',
        'price',
        'duration',
        'mode',
        'level',
        'certification',
        'prerequisites',
        'image_url',
        'brochure_url',
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
        return $this->hasMany(Enrollment::class);
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
            'module_id',   // Foreign key on IntakeModule (pointing to Module)
            'id',          // Primary key on Intake
            'id',          // Local key on Course
            'intake_id'    // Foreign key on IntakeModule (pointing to Intake)
        )->whereHas('module', function ($query) {
            $query->where('course_id', $this->id);
        })->distinct();
    }


    // Students who have ever enrolled in this course
    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            Enrollment::class,
            'course_id',  // FK on enrolments
            'id',         // PK on student
            'id',         // PK on courses
            'student_id'  // FK on enrolments
        )->distinct();
    }

    // App\Models\Course.php
    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'course_lecturer');
    }

}
