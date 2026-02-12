<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;

class OrderController extends Controller
{
    private function getCartOrFail(Request $request): Cart
    {
        $user = $request->user();

        if ($user) {
            $cart = Cart::query()->where('user_id', $user->id)->first();
        } else {
            $token = (string) $request->header('X-Cart-Token', '');
            $cart = $token ? Cart::query()->where('token', $token)->whereNull('user_id')->first() : null;
        }

        abort_unless($cart, 404, 'Cart not found');
        $cart->load(['items.variant.product']);

        abort_if($cart->items->isEmpty(), 422, 'Cart is empty');

        return $cart;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:cod,stripe,bank'],
            'customer_note' => ['nullable', 'string', 'max:2000'],

            'address.full_name' => ['required', 'string', 'max:255'],
            'address.phone' => ['required', 'string', 'max:50'],
            'address.email' => ['nullable', 'email', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.address_line1' => ['required', 'string', 'max:255'],
            'address.address_line2' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['nullable', 'string', 'max:50'],
            'address.note' => ['nullable', 'string', 'max:2000'],
        ]);

        $cart = $this->getCartOrFail($request);
        $user = $request->user();

        $orderId = DB::transaction(function () use ($validated, $cart, $user) {
            // Re-load variants FOR UPDATE to avoid race conditions
            $variantIds = $cart->items->pluck('product_variant_id')->all();

            /** @var \Illuminate\Support\Collection<int, ProductVariant> $variants */
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validate stock & compute totals (denari)
            $subtotal = 0;
            $itemsPayload = [];

            foreach ($cart->items as $cartItem) {
                $variant = $variants->get($cartItem->product_variant_id);
                abort_unless($variant && $variant->is_active, 422, 'Invalid variant');

                if ($variant->track_stock && $variant->stock_qty < $cartItem->qty) {
                    abort(422, 'Недоволна залиха за ' . ($variant->sku ?? 'item'));
                }

                $priceDen = (int) round(($variant->sale_price_cents ?? $variant->price_cents) / 100);
                $line = $priceDen * (int) $cartItem->qty;

                $subtotal += $line;

                $itemsPayload[] = [
                    'variant' => $variant,
                    'qty' => (int) $cartItem->qty,
                    'unit_price_den' => $priceDen,
                    'line_total_den' => $line,
                ];
            }

            // Shipping (MVP: fixed 0, later: by city)
            $shipping = 0;
            $total = $subtotal + $shipping;

            $address = Address::query()->create([
                'user_id' => $user?->id,
                ...$validated['address'],
            ]);

            $order = Order::query()->create([
                'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'user_id' => $user?->id,
                'address_id' => $address->id,

                'status' => 'new',
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'cod' ? 'unpaid' : 'unpaid',

                'subtotal_den' => $subtotal,
                'shipping_den' => $shipping,
                'total_den' => $total,

                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            foreach ($itemsPayload as $row) {
                /** @var ProductVariant $variant */
                $variant = $row['variant'];

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'product_name' => $variant->product?->name ?? 'Product',
                    'variant_name' => $variant->name,
                    'unit_price_den' => $row['unit_price_den'],
                    'qty' => $row['qty'],
                    'line_total_den' => $row['line_total_den'],
                ]);

                // Reduce stock
                if ($variant->track_stock) {
                    $variant->decrement('stock_qty', $row['qty']);
                }
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'new',
                'changed_by_user_id' => $user?->id,
                'note' => 'Order created',
            ]);

            // Clear cart
            $cart->items()->delete();

            return $order->id;
        });

        $order = Order::query()->with(['items', 'address', 'user'])->findOrFail($orderId);

        $clientSecret = null;

        if ($order->payment_method === 'stripe') {
            try {
                $pi = app(StripeService::class)->createOrGetPaymentIntent($order);
                $clientSecret = $pi['client_secret'];
            } catch (ApiConnectionException $e) {
                return response()->json([
                    'message' => 'Payment provider unavailable. Please try again.',
                ], 503);
            } catch (AuthenticationException $e) {
                return response()->json([
                    'message' => 'Stripe credentials are invalid.',
                ], 500);
            } catch (ApiErrorException $e) {
                return response()->json([
                    'message' => 'Stripe error: ' . $e->getMessage(),
                ], 422);
            }
        }

        $order->refresh()->load(['items', 'address']);
        \App\Jobs\SendOrderCreatedEmailJob::dispatch($order->id);

        return response()->json([
            'data' => $order,
            'stripe' => $clientSecret ? [
                'client_secret' => $clientSecret,
                'payment_intent_id' => $order->stripe_payment_intent_id,
                'publishable_key' => config('services.stripe.publishable'),
            ] : null,
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'address'])
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($user && $order->user_id === $user->id, 404);

        $order->load(['items', 'address', 'history']);

        return response()->json(['data' => $order]);
    }
}
