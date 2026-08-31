<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
    ];

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function voteHead()
    {
        return $this->belongsTo(VoteHead::class);
    }

    public function subVoteHead()
    {
        return $this->belongsTo(SubVoteHead::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
