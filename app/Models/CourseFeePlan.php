<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseFeePlan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'mandatory' => 'boolean',
        'amount' => 'decimal:2',
        'trimester_sequence' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function feeDefinition()
    {
        return $this->belongsTo(FeeDefinition::class);
    }
}
