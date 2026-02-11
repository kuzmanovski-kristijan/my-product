<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    private function denars(?int $cents): ?int
    {
        return $cents === null ? null : (int) round($cents / 100);
    }

    public function toArray(Request $request): array
    {
        // These fields come from selectSub in controller (min_price_cents, has_stock)
        $minPrice = $this->denars($this->min_price_cents ?? null);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,

            'price_from' => $minPrice,
            'in_stock' => (bool) ($this->has_stock ?? false),

            'thumbnail' => $this->whenLoaded('images', function () {
                $img = $this->images->first();

                return $img?->path ? url('storage/' . ltrim($img->path, '/')) : null;
            }),
        ];
    }
}
