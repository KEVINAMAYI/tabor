<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
