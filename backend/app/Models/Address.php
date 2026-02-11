<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'email',
        'city',
        'address_line1',
        'address_line2',
        'postal_code',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
