<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'sku',
        'product_name',
        'variant_name',
        'unit_price_den',
        'qty',
        'line_total_den',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
