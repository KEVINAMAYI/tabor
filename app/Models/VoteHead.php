<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteHead extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function expenseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'expense_account_id');
    }

    public function subVoteHeads()
    {
        return $this->hasMany(SubVoteHead::class);
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
