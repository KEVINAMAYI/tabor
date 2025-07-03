<?php

namespace App\Models;

use App\Models\Module;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

class IntakeModule extends Pivot
{

    protected $table = 'intake_modules'; // ✅ your actual pivot table name

    // we kept an auto‑increment id, so leave $incrementing = true
    protected $fillable = [
        'intake_id',
        'module_id',
    ];

    /* ------------------------------------------------------------
     | Relationships
     |------------------------------------------------------------ */

    public function intake()
    {
        return $this->belongsTo(Intake::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    // lecturers assigned via the second pivot
    public function lecturers()
    {
        return $this->belongsToMany(
            Lecturer::class,
            'intake_module_lecturers'   // child pivot table
        )->withTimestamps();
    }

    // teaching sessions (for attendance)
    public function sessions()
    {
        return $this->hasMany(ModuleSession::class);
    }

    // materials uploaded for this module in this intake
    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    // assessments (quizzes / assignments) in this intake
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }


    /**
     * Get a unique collection of Course models for a given intake.
     */
    public function scopeCoursesForIntake(Builder $query, int $intakeId)
    {
        return $query->where('intake_id', $intakeId)
            ->with('module.course')
            ->get()
            ->pluck('module.course')
            ->unique('id')
            ->values();
    }


    public static function getModulesForIntakeCourse(int $intakeId, int $courseId)
    {
        return Module::where('course_id', $courseId)
            ->whereIn('id', function ($query) use ($intakeId) {
                $query->select('module_id')
                    ->from('intake_modules')
                    ->where('intake_id', $intakeId);
            })
            ->get();
    }
}

