<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

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

    public function trimesters()
    {
        return $this->hasMany(CourseTrimester::class)
            ->orderBy('trimester_number');
    }

    public static function createOrUpdateCourseTrimesters($course)
    {
        $feePerTrimester = round(
            $course->price / $course->number_of_trimesters,
            2
        );

        // 1. Create or update required trimesters
        for ($i = 1; $i <= $course->number_of_trimesters; $i++) {
            CourseTrimester::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'trimester_number' => $i,
                ],
                [
                    // 'duration_months' => $durationPerTrimester,
                    'fee_amount' => $feePerTrimester,
                ]
            );
        }

        // 2. Remove excess trimesters
        CourseTrimester::where('course_id', $course->id)
            ->where('trimester_number', '>', $course->number_of_trimesters)
            ->delete();
    }



}
