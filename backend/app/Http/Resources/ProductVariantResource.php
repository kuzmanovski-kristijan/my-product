<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    private function denars(?int $cents): ?int
    {
        return $cents === null ? null : (int) round($cents / 100);
    }

    public function toArray(Request $request): array
    {
        $price = $this->denars($this->price_cents);
        $sale = $this->denars($this->sale_price_cents);

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'color' => $this->color,
            'material' => $this->material,
            'dimensions' => $this->dimensions,

            'price' => $price,
            'sale_price' => $sale,
            'final_price' => $sale ?? $price,

            'stock_qty' => (int) $this->stock_qty,
            'track_stock' => (bool) $this->track_stock,
            'is_active' => (bool) $this->is_active,
            'in_stock' => $this->track_stock ? ((int) $this->stock_qty > 0) : true,
        ];
    }
}
