<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'description', 'price'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class)->withTimestamps()->withPivot('enrolled_at');
    }

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class)->withTimestamps();
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}

