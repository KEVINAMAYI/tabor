<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'entry_date' => 'date',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function isBalanced(): bool
    {
        $totals = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();

        return round((float) $totals->sum('debit'), 2) === round((float) $totals->sum('credit'), 2);
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
