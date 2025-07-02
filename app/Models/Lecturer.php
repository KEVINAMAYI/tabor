<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;

    /**
     * Mass‑assignable columns.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'kra_pin',
        'id_number',
        'next_of_kin',
        'alternative_contact',
        'user_id',
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    // Portal credentials / messaging account
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Pivot rows: the lecturer–module link for each intake
    public function intakeModules()
    {
        return $this->belongsToMany(
            IntakeModule::class,
            'intake_module_lecturers'   // pivot table
        )->withTimestamps();
    }

    /* -----------------------------------------------------------------
     |  Indirect (through) relationships
     |------------------------------------------------------------------
     */

    // Actual modules they teach (distinct)
    public function modules()
    {
        return $this->hasManyThrough(
            Module::class,
            IntakeModule::class,
            'id',           // PK on intake_modules
            'id',           // PK on modules
            'id',           // PK on lecturers
            'module_id'     // FK on intake_modules
        )->distinct();
    }

    // Courses (programmes) touched by those modules
    public function courses()
    {
        return $this->hasManyThrough(
            Course::class,
            Module::class,
            'id',           // PK on modules
            'id',           // PK on courses
            'id',           // PK on lecturers
            'course_id'     // FK on modules
        )->distinct();
    }

    // Intakes they’re teaching in
    public function intakes()
    {
        return $this->hasManyThrough(
            Intake::class,
            IntakeModule::class,
            'id',            // PK on intake_modules
            'id',            // PK on intakes
            'id',            // PK on lecturers
            'intake_id'      // FK on intake_modules
        )->distinct();
    }

    // Teaching sessions generated for attendance
    public function sessions()
    {
        return $this->hasManyThrough(
            ModuleSession::class,
            IntakeModule::class,
            'id',            // PK on intake_modules
            'intake_module_id',
            'id',
            'id'
        );
    }

    /* -----------------------------------------------------------------
     |  Accessors / helpers
     |------------------------------------------------------------------
     */

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
