<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_checkout_with_cod_and_cart_is_cleared(): void
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
            'sale_price_cents' => 90000,
            'stock_qty' => 5,
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
            ->assertCreated();

        $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson('/api/orders', [
                'payment_method' => 'cod',
                'customer_note' => 'Leave at door',
                'address' => [
                    'full_name' => 'Guest User',
                    'phone' => '+38970000000',
                    'email' => 'guest@example.com',
                    'city' => 'Skopje',
                    'address_line1' => 'Street 1',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.payment_method', 'cod')
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.payment_status', 'unpaid');

        $this->withHeader('X-Cart-Token', $cartToken)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.subtotal', 0)
            ->assertJsonCount(0, 'data.items');
    }
}
