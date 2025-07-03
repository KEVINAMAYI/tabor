<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'course_id',
        'title',         // e.g. “Programming I”
        'description',
        'price',         // per‑module fee, if you charge separately
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    // Parent course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Pivot rows (this module offered in various intakes)
    public function intakeModules()
    {
        return $this->hasMany(IntakeModule::class);
    }

    // Intakes (many‑to‑many via intake_modules)
    public function intakes()
    {
        return $this->belongsToMany(
            Intake::class,
            'intake_modules'
        )
            ->using(IntakeModule::class)   // custom pivot model
            ->withPivot('id')              // keep pivot id for easy joins
            ->withTimestamps();
    }

    /* -----------------------------------------------------------------
     |  Indirect / convenience relationships
     |------------------------------------------------------------------
     */

    // Lecturers assigned through the pivot
    public function lecturers()
    {
        return $this->belongsToMany(
            Lecturer::class,
            'intake_module_lecturers',
            'module_id',        // requires module_id column on the pivot;
            // add one if you haven't already
            'lecturer_id'
        )->withTimestamps()->distinct();
    }

    // Materials uploaded for this module in any intake
    public function materials()
    {
        return $this->hasManyThrough(
            Material::class,
            IntakeModule::class,
            'module_id',            // FK on intake_modules
            'intake_module_id',     // FK on materials
            'id',
            'id'
        );
    }

    // Assessments linked via intake_modules
    public function assessments()
    {
        return $this->hasManyThrough(
            Assessment::class,
            IntakeModule::class,
            'module_id',
            'intake_module_id'
        );
    }

    // Teaching sessions (for attendance)
    public function sessions()
    {
        return $this->hasManyThrough(
            ModuleSession::class,
            IntakeModule::class,
            'module_id',
            'intake_module_id'
        );
    }

    // Students who’ve taken this module (through enrolments → intake_modules)
    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'enrolments',          // via enrolments then a join to intake_modules
            'course_id',
            'student_id'
        )->distinct();
    }


    public function classGroups()
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_module')->withTimestamps();
    }


}
