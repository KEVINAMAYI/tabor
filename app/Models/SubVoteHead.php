<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubVoteHead extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function voteHead()
    {
        return $this->belongsTo(VoteHead::class);
    }

    public function expenses()
    {
        return $this->hasMany(PettyCashExpense::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
