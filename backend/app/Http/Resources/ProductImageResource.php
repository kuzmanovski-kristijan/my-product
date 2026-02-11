<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->path ? url('storage/' . ltrim($this->path, '/')) : null,
            'path' => $this->path,
            'alt' => $this->alt,
            'sort_order' => $this->sort_order,
        ];
    }
}
