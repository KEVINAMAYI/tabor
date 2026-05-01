<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeDefinition extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'applies_once' => 'boolean',
        'mandatory' => 'boolean',
        'active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function courseFeePlans()
    {
        return $this->hasMany(CourseFeePlan::class);
    }
}
