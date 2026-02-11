<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function denars(?int $cents): ?int
    {
        return $cents === null ? null : (int) round($cents / 100);
    }

    private function cartSummary(Cart $cart): array
    {
        $cart->load([
            'items.variant.product:id,name,slug',
        ]);

        $items = $cart->items->map(function (CartItem $item) {
            $v = $item->variant;

            $price = $this->denars($v->price_cents);
            $sale = $this->denars($v->sale_price_cents);
            $final = $sale ?? $price;

            return [
                'id' => $item->id,
                'qty' => (int) $item->qty,
                'variant' => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'name' => $v->name,
                    'color' => $v->color,
                    'material' => $v->material,
                    'dimensions' => $v->dimensions,
                    'price' => $price,
                    'sale_price' => $sale,
                    'final_price' => $final,
                    'in_stock' => $v->track_stock ? ((int) $v->stock_qty > 0) : true,
                    'stock_qty' => (int) $v->stock_qty,
                    'product' => [
                        'id' => $v->product?->id,
                        'name' => $v->product?->name,
                        'slug' => $v->product?->slug,
                    ],
                ],
                'line_total' => $final * (int) $item->qty,
            ];
        })->values();

        $subtotal = (int) $items->sum('line_total');

        return [
            'id' => $cart->id,
            'token' => $cart->token,
            'user_id' => $cart->user_id,
            'items' => $items,
            'subtotal' => $subtotal, // денари
        ];
    }

    private function getOrCreateCart(Request $request): Cart
    {
        // If authenticated: try find user's cart
        $user = $request->user();
        if ($user) {
            return Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['token' => (string) Str::uuid()]
            );
        }

        // Guest: use token from header
        $token = $request->header('X-Cart-Token');
        if (is_string($token) && $token !== '') {
            $cart = Cart::query()->where('token', $token)->whereNull('user_id')->first();
            if ($cart) {
                return $cart;
            }
        }

        // Create new guest cart
        return Cart::query()->create([
            'token' => (string) Str::uuid(),
            'user_id' => null,
        ]);
    }

    public function show(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        return response()->json([
            'data' => $this->cartSummary($cart),
        ]);
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $cart = $this->getOrCreateCart($request);

        $variant = ProductVariant::query()->where('id', $validated['variant_id'])->where('is_active', true)->firstOrFail();

        // Stock check (basic)
        if ($variant->track_stock && $variant->stock_qty < $validated['qty']) {
            return response()->json([
                'message' => 'Недоволна залиха.',
            ], 422);
        }

        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
        ]);

        $newQty = (int) ($item->exists ? ($item->qty + $validated['qty']) : $validated['qty']);

        if ($variant->track_stock && $variant->stock_qty < $newQty) {
            return response()->json([
                'message' => 'Недоволна залиха за бараната количина.',
            ], 422);
        }

        $item->qty = $newQty;
        $item->save();

        return response()->json([
            'data' => $this->cartSummary($cart),
        ], 201);
    }

    public function updateItem(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $cart = $this->getOrCreateCart($request);

        abort_unless($cartItem->cart_id === $cart->id, 404);

        $variant = $cartItem->variant;

        if ($variant->track_stock && $variant->stock_qty < $validated['qty']) {
            return response()->json([
                'message' => 'Недоволна залиха.',
            ], 422);
        }

        $cartItem->qty = (int) $validated['qty'];
        $cartItem->save();

        return response()->json([
            'data' => $this->cartSummary($cart),
        ]);
    }

    public function removeItem(Request $request, CartItem $cartItem)
    {
        $cart = $this->getOrCreateCart($request);

        abort_unless($cartItem->cart_id === $cart->id, 404);

        $cartItem->delete();

        return response()->json([
            'data' => $this->cartSummary($cart),
        ]);
    }

    /**
     * Optional: merge guest cart into user cart when user logs in.
     */
    public function merge(Request $request)
    {
        $request->validate([
            'token' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $guest = Cart::query()->where('token', $request->input('token'))->whereNull('user_id')->first();
        $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id], ['token' => (string) Str::uuid()]);

        if (! $guest) {
            return response()->json(['data' => $this->cartSummary($userCart)]);
        }

        foreach ($guest->items as $guestItem) {
            $existing = CartItem::query()->firstOrNew([
                'cart_id' => $userCart->id,
                'product_variant_id' => $guestItem->product_variant_id,
            ]);

            $existing->qty = (int) ($existing->exists ? ($existing->qty + $guestItem->qty) : $guestItem->qty);
            $existing->save();
        }

        $guest->delete();

        return response()->json(['data' => $this->cartSummary($userCart)]);
    }
}
