<?php

namespace Tests\Feature;

use App\Jobs\SendOrderStatusChangedEmailJob;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_intent_succeeded_marks_order_paid_and_confirmed(): void
    {
        Queue::fake();

        config()->set('services.stripe.webhook_secret', 'whsec_test_secret');

        $address = Address::query()->create([
            'full_name' => 'Guest User',
            'phone' => '+38970000000',
            'email' => 'guest@example.com',
            'city' => 'Skopje',
            'address_line1' => 'Street 1',
        ]);

        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-1001',
            'address_id' => $address->id,
            'status' => 'new',
            'payment_method' => 'stripe',
            'payment_status' => 'unpaid',
            'stripe_payment_intent_id' => 'pi_test_123',
            'currency' => 'mkd',
            'subtotal_den' => 1000,
            'shipping_den' => 0,
            'total_den' => 1000,
        ]);

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'new',
            'note' => 'Order created',
        ]);

        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_test_secret');
        $sig = "t={$timestamp},v1={$signature}";

        $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Stripe-Signature' => $sig,
            ],
            $payload
        )
            ->assertOk()
            ->assertJson(['ok' => true]);

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);

        $latestHistory = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($latestHistory);
        $this->assertSame('new', $latestHistory->from_status);
        $this->assertSame('confirmed', $latestHistory->to_status);
        $this->assertSame('Stripe payment succeeded', $latestHistory->note);

        Queue::assertPushed(SendOrderStatusChangedEmailJob::class);
    }
}
