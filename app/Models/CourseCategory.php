<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    protected $table = 'course_categories';

    protected $guarded = ['id'];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
