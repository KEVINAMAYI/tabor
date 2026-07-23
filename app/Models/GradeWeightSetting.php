<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeWeightSetting extends Model
{
    protected $fillable = [
        'assessment_type',
        'label',
        'weight_percentage',
        'max_per_module',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'weight_percentage' => 'float',
        'max_per_module'    => 'integer',
        'sort_order'        => 'integer',
        'is_active'         => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function forType(string $type): ?self
    {
        return static::where('assessment_type', $type)->where('is_active', true)->first();
    }
}
