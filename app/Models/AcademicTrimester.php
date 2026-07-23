<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicTrimester extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'teaching_start_date' => 'date',
        'teaching_end_date'   => 'date',
        'holiday_start_date'  => 'date',
        'holiday_end_date'    => 'date',
    ];
}
