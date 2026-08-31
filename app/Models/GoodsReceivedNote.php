<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GoodsReceivedNote extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getDeliveryNoteUrlAttribute(): ?string
    {
        return $this->delivery_note_path ? Storage::url($this->delivery_note_path) : null;
    }
}
