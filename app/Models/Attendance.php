<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * Table name (Laravel’s default pluralisation already matches `attendances`,
     * so you can omit this.  Include it only if you changed the name.)
     */
    // protected $table = 'attendances';

    /**
     * Mass‑assignable columns.
     */
    protected $fillable = [
        'module_session_id',
        'enrollment_id',
        'status',          // present | absent | late
    ];

    /**
     * Casts.
     */
    protected $casts = [
        'status' => 'string',
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    public function session()
    {
        return $this->belongsTo(ModuleSession::class, 'module_session_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /* -----------------------------------------------------------------
     |  Convenience hops
     |------------------------------------------------------------------
     */

    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            Enrollment::class,
            'id',          // PK on enrollments
            'id',          // PK on students
            'enrollment_id',
            'student_id'
        );
    }

    public function module()
    {
        return $this->hasOneThrough(
            Module::class,
            ModuleSession::class,
            'id',          // PK on sessions
            'id',          // PK on modules
            'module_session_id',
            'module_id'
        );
    }

    public function intake()
    {
        return $this->hasOneThrough(
            Intake::class,
            Enrollment::class,
            'id',
            'id',
            'enrollment_id',
            'intake_id'
        );
    }

    /* -----------------------------------------------------------------
     |  Query scopes
     |------------------------------------------------------------------
     */

    public function scopePresent($q)  { return $q->where('status', 'present'); }
    public function scopeAbsent($q)   { return $q->where('status', 'absent');  }
    public function scopeLate($q)     { return $q->where('status', 'late');    }

    // e.g. Attendance::forStudent($id)->late()
    public function scopeForStudent($q, int $studentId)
    {
        return $q->whereHas('enrollment', fn ($e) => $e->where('student_id', $studentId));
    }

    public function scopeForModule($q, int $moduleId)
    {
        return $q->whereHas('session', fn ($s) => $s->where('module_id', $moduleId));
    }
}
