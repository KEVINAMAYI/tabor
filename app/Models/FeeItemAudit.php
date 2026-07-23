<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeItemAudit extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function studentFeeItem()
    {
        return $this->belongsTo(StudentFeeItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * "amount: 28,500 → 25,000, description: ..." summary of what changed.
     */
    public function getChangeSummaryAttribute(): string
    {
        $parts = [];

        foreach ($this->new_values ?? [] as $field => $new) {
            $old = $this->old_values[$field] ?? null;

            if (in_array($field, ['amount', 'balance', 'amount_paid'], true)) {
                $old = $old !== null ? number_format((float) $old, 0) : '-';
                $new = $new !== null ? number_format((float) $new, 0) : '-';
            }

            $parts[] = str_replace('_', ' ', $field) . ": {$old} → {$new}";
        }

        return implode(', ', $parts);
    }
}
