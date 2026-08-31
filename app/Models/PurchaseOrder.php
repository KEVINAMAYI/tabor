<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PurchaseOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goodsReceivedNote()
    {
        return $this->hasOne(GoodsReceivedNote::class);
    }

    public function invoice()
    {
        return $this->hasOne(SupplierInvoice::class);
    }

    public function getDocumentUrlAttribute(): ?string
    {
        return $this->document_path ? Storage::url($this->document_path) : null;
    }
}
