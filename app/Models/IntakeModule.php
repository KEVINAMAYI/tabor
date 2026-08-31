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
        'intake_id', // legacy — kept for historical data, no longer written by new assignments
        'trimester_id',
        'module_id',
    ];

    /* ------------------------------------------------------------
     | Relationships
     |------------------------------------------------------------ */

    // legacy — superseded by trimester(), kept so old records/UI paths still resolve
    public function intake()
    {
        return $this->belongsTo(Intake::class);
    }

    public function trimester()
    {
        return $this->belongsTo(Trimester::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'intake_id', 'intake_id');
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
            'intake_module_lecturers',
            'intake_module_id',
            'lecturer_id'
        )->withTimestamps();
    }

    // teaching sessions (for attendance)
    public function sessions()
    {
        return $this->hasMany(ModuleSession::class, 'intake_module_id');
    }

    // materials uploaded for this module in this intake
    public function materials()
    {
        return $this->hasMany(Material::class, 'intake_module_id');
    }

    // assessments (CATs / exams / assignments) in this intake
    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'intake_module_id');
    }

    // announcements posted by lecturers
    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'intake_module_id')->orderByDesc('is_pinned')->orderByDesc('published_at');
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

