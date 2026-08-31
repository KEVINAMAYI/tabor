<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeContainingDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)->where('end_date', '>=', $date);
    }
}
