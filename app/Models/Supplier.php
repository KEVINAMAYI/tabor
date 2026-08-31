<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
