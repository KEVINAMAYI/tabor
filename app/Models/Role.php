<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function users()
    {
        return $this->morphedByMany(Student::class, 'user')->withTimestamps()
            ->union($this->morphedByMany(Lecturer::class, 'user')->withTimestamps());
    }
}
