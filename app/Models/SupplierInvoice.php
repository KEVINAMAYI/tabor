<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SupplierInvoice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return round((float) $this->amount - (float) $this->amount_paid, 2);
    }

    public function getInvoiceDocumentUrlAttribute(): ?string
    {
        return $this->invoice_document_path ? Storage::url($this->invoice_document_path) : null;
    }
}
