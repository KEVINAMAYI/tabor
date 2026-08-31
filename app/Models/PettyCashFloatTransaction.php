<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashFloatTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function custodian()
    {
        return $this->belongsTo(PettyCashCustodian::class, 'custodian_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
