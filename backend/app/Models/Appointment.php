<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'starts_at',
        'ends_at',
        'full_name',
        'phone',
        'email',
        'status',
        'note',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
