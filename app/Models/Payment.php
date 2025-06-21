<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'payable_type',
        'payable_id',
        'amount',
        'method',
        'reference',
        'is_paid',
        'paid_at'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }
}
