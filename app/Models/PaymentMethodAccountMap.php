<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodAccountMap extends Model
{
    protected $table = 'payment_method_account_map';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
