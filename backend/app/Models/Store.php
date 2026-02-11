<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['name', 'city', 'address', 'phone', 'is_active'];

    public function appointments()
    {
        return $this->hasMany(\App\Models\Appointment::class);
    }
}
