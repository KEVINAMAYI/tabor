<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PettyCashExpense extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function custodian()
    {
        return $this->belongsTo(PettyCashCustodian::class, 'custodian_id');
    }

    public function voteHead()
    {
        return $this->belongsTo(VoteHead::class);
    }

    public function subVoteHead()
    {
        return $this->belongsTo(SubVoteHead::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? Storage::url($this->receipt_path) : null;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
