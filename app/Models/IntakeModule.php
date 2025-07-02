<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class IntakeModule extends Pivot
{


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
}

