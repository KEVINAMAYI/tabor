<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone','user_id'];

    public function modules()
    {
        return $this->belongsToMany(Module::class)->withTimestamps();
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
