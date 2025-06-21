<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = ['module_id', 'type', 'title', 'date', 'total_marks'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}

