<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    protected $guarded = ['id'];

    public function feeDefinitions()
    {
        return $this->hasMany(FeeDefinition::class);
    }

    public function revenueAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'revenue_account_id');
    }
}
