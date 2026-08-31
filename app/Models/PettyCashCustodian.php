<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashCustodian extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'opening_float' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function floatTransactions()
    {
        return $this->hasMany(PettyCashFloatTransaction::class, 'custodian_id');
    }

    public function expenses()
    {
        return $this->hasMany(PettyCashExpense::class, 'custodian_id');
    }

    /**
     * Opening float + all float issuances/reimbursements - approved expenses.
     * Pending/rejected expenses do not affect the balance.
     */
    public function getAvailableBalanceAttribute(): float
    {
        $issued = (float) $this->floatTransactions()->sum('amount');
        $spent = (float) $this->expenses()->where('status', 'approved')->sum('amount');

        return round((float) $this->opening_float + $issued - $spent, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
