<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'color',
        'material',
        'dimensions',
        'price_cents',
        'sale_price_cents',
        'stock_qty',
        'track_stock',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
