<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    /** Mass‑assignable columns */
    protected $fillable = [
        'intake_module_id',   // FK to intake_modules
        'type',               // CAT | Exam
        'title',
        'due_on',
        'max_marks',
    ];

    /** Casts */
    protected $casts = [
        'due_on' => 'date',
        'max_marks' => 'integer',
    ];

    /* -----------------------------------------------------------------
     |  Direct relationships
     |------------------------------------------------------------------
     */

    public function intakeModule()
    {
        return $this->belongsTo(IntakeModule::class);
    }

    /* -----------------------------------------------------------------
     |  Convenience relationships
     |------------------------------------------------------------------
     */

    public function module()
    {
        return $this->hasOneThrough(
            Module::class,
            IntakeModule::class,
            'id',          // PK on intake_modules
            'id',          // PK on modules
            'intake_module_id',
            'module_id'
        );
    }

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

    // Student submissions / marks
    public function submissions()
    {
        return $this->hasMany(AssessmentSubmission::class);
    }


    /* -----------------------------------------------------------------
     |  Scopes
     |------------------------------------------------------------------
     */

    // e.g. Assessment::ofType('Exam')->future()
    public function scopeOfType($q, string $type)
    {
        return $q->where('type', $type);
    }

    public function scopeFuture($q)
    {
        return $q->whereDate('due_on', '>=', now());
    }
}
