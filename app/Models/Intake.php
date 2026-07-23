<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intake extends Model
{
    use HasFactory;

    /**
     * Mass‑assignable columns
     */
    protected $fillable = [
        'name',         // e.g. “Jan 2026”
        'starts_at',
        'ends_at',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    // Pivot rows for the modules offered in this intake
    public function intakeModules()
    {
        return $this->hasMany(IntakeModule::class);
    }

    // Modules (many‑to‑many through pivot)
    public function modules()
    {
        return $this->belongsToMany(
            Module::class,
            'intake_modules'
        )->using(IntakeModule::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    /* -----------------------------------------------------------------
     |  Indirect convenience relationships
     |------------------------------------------------------------------
     */

    // Courses reached through modules
    public function courses()
    {
        return $this->hasManyThrough(
            Course::class,
            Module::class,
            'id',          // PK on modules
            'id',          // PK on courses
            'id',          // PK on intakes
            'course_id'    // FK on modules
        )->distinct();
    }

    // Lecturers teaching in this intake
    public function lecturers()
    {
        return $this->belongsToMany(
            Lecturer::class,
            'intake_module_lecturers',   // child pivot
            'intake_id',                 // requires intake_id column on that pivot
            'lecturer_id'
        )->distinct();
    }

    // Enrolments scoped to this intake
    public function enrolments()
    {
        return $this->hasMany(Enrollment::class);
    }

    // Students enrolled in any course this intake
    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            Enrollment::class,
            'intake_id',
            'id',
            'id',
            'student_id'
        )->distinct();
    }

    // Teaching sessions generated from intake‑modules
    public function sessions()
    {
        return $this->hasManyThrough(
            ModuleSession::class,
            IntakeModule::class,
            'intake_id',
            'intake_module_id'
        );
    }

    /* -----------------------------------------------------------------
     |  Query scopes
     |------------------------------------------------------------------
     */

    // Scope: only intakes currently active today
    public function scopeCurrent($query)
    {
        return $query->whereDate('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', now());
            });
    }

    // Scope by academic year string (e.g. “2025”)
    public function scopeYear($query, string $year)
    {
        return $query->whereYear('starts_at', $year);
    }
}
