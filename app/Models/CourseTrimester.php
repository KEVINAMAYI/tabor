<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseTrimester extends Model
{
    protected $guarded = ['id'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
