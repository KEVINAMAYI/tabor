<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequisition extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'needed_by_date' => 'date',
        'dept_approved_at' => 'datetime',
        'finance_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function voteHead()
    {
        return $this->belongsTo(VoteHead::class);
    }

    public function subVoteHead()
    {
        return $this->belongsTo(SubVoteHead::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function deptApprovedBy()
    {
        return $this->belongsTo(User::class, 'dept_approved_by');
    }

    public function financeApprovedBy()
    {
        return $this->belongsTo(User::class, 'finance_approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class);
    }
}
