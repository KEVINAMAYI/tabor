<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class IntakeModuleLecturer extends Pivot
{

    protected $table = 'intake_module_lecturers'; // 👈 specify pivot table

    protected $fillable = [
        'intake_module_id',
        'lecturer_id',
    ];

    public $incrementing = true;   // because we kept an id column

    /* -----------------------------------------------------------------
     |  Convenience relationships
     |------------------------------------------------------------------
     */

    public function intakeModule()
    {
        return $this->belongsTo(IntakeModule::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }
}
