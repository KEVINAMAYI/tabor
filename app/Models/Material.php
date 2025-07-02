<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    use HasFactory;

    /**
     * Mass‑assignable attributes.
     */
    protected $fillable = [
        'intake_module_id',
        'file_path',        // storage / S3 key
        'original_name',
        'mime',
    ];

    /* -----------------------------------------------------------------
     |  Relationships
     |------------------------------------------------------------------
     */

    // Direct: the pivot row that links a module to an intake
    public function intakeModule()
    {
        return $this->belongsTo(IntakeModule::class);
    }

    // Convenience hop to the underlying module
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

    // Convenience hop to the course
    public function course()
    {
        return $this->hasOneThrough(
            Course::class,
            Module::class,
            'id',          // PK on modules
            'id',          // PK on courses
            'id',          // PK on materials
            'course_id'
        );
    }

    // Convenience hop to the intake
    public function intake()
    {
        return $this->hasOneThrough(
            Intake::class,
            IntakeModule::class,
            'id',          // PK on intake_modules
            'id',          // PK on intakes
            'intake_module_id',
            'intake_id'
        );
    }


    /* -----------------------------------------------------------------
     |  Accessors & helpers
     |------------------------------------------------------------------
     */

    /**
     * Public URL to download / view the file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * File extension (lower‑case)
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }
}
