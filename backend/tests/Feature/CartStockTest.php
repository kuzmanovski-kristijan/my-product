<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_add_more_than_available_stock(): void
    {
        $category = Category::query()->create([
            'name' => 'Sofas',
            'slug' => 'sofas',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Luna',
            'slug' => 'luna',
            'is_active' => true,
            'is_featured' => false,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'LUNA-GRAY',
            'name' => 'Luna Gray',
            'price_cents' => 100000,
            'stock_qty' => 1,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $cartToken = $this->getJson('/api/cart')
            ->assertOk()
            ->json('data.token');

        $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson('/api/cart/items', [
                'variant_id' => $variant->id,
                'qty' => 2,
            ])
            ->assertStatus(422);
    }
}
