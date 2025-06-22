<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'dob', 'user_id'];

    public function courses()
    {
        return $this->belongsToMany(Course::class,'enrollments')->withTimestamps()->withPivot('enrolled_at');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class)->withTimestamps()->withPivot('enrolled_at');
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class)->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'user')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

