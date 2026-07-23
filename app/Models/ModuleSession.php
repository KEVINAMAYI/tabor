<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonInterface;

class ModuleSession extends Model
{
    use HasFactory;

    /* -----------------------------------------------------------------
     | Mass‑assignable columns
     |------------------------------------------------------------------
     */
    protected $fillable = [
        'intake_module_id',
        'starts_at',
        'ends_at',
        'room',
        'topic',
    ];

    /* -----------------------------------------------------------------
     | Casts
     |------------------------------------------------------------------
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    /* -----------------------------------------------------------------
     | Direct relationships
     |------------------------------------------------------------------
     */

    public function intakeModule()
    {
        return $this->belongsTo(IntakeModule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /* -----------------------------------------------------------------
     | Convenience hops
     |------------------------------------------------------------------
     */

    // The actual module (CS101)
    public function module()
    {
        return $this->hasOneThrough(
            Module::class,
            IntakeModule::class,
            'id',              // PK on intake_modules
            'id',              // PK on modules
            'intake_module_id',
            'module_id'
        );
    }

    // The intake (Jan 2026)
    public function intake()
    {
        return $this->hasOneThrough(
            Intake::class,
            IntakeModule::class,
            'id',
            'id',
            'intake_module_id',
            'intake_id'
        );
    }

    // Lecturers teaching this session (via intake_module_lecturers)
    public function lecturers()
    {
        return $this->belongsToMany(
            Lecturer::class,
            'intake_module_lecturers',
            'intake_module_id',
            'lecturer_id',
            'intake_module_id',
            'id'
        )->wherePivot('intake_module_id', $this->intake_module_id)->distinct();
    }

    // Students assigned to this session (through attendance)
    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'attendance',
            'module_session_id',
            'enrollment_id'   // resolves through Enrollment model
        )->using(Attendance::class)      // pivot model
        ->withPivot('status')
            ->withTimestamps();
    }

    /* -----------------------------------------------------------------
     | Query scopes
     |------------------------------------------------------------------
     */

    // upcoming sessions
    public function scopeFuture($q)
    {
        return $q->where('starts_at', '>', now());
    }

    // sessions between two dates
    public function scopeBetween($q, CarbonInterface $from, CarbonInterface $to)
    {
        return $q->whereBetween('starts_at', [$from, $to]);
    }

    /* -----------------------------------------------------------------
     | Helpers
     |------------------------------------------------------------------
     */

    public function getDurationMinutesAttribute(): ?int
    {
        return $this->ends_at
            ? $this->starts_at->diffInMinutes($this->ends_at)
            : null;
    }

    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }
}
