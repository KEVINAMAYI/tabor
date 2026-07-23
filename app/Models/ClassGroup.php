<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    protected $fillable = ['intake_id', 'name'];

    public function intake()
    {
        return $this->belongsTo(Intake::class);
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'class_group_module')->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
