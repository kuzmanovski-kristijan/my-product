<?php

namespace App\Services;

use App\Models\Order;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $secret = config('services.stripe.secret');
        $this->stripe = new StripeClient($secret);
    }

    /**
     * Creates or reuses a PaymentIntent for the order.
     * Amount in Stripe is minor units; we store totals in denars (integer),
     * so amount = total_den * 100.
     */
    public function createOrGetPaymentIntent(Order $order): array
    {
        if ($order->stripe_payment_intent_id) {
            $pi = $this->stripe->paymentIntents->retrieve($order->stripe_payment_intent_id, []);
            return [
                'id' => $pi->id,
                'client_secret' => $pi->client_secret,
                'status' => $pi->status,
            ];
        }

        $currency = $order->currency ?: config('services.stripe.currency', 'mkd');

        $amount = (int) $order->total_den * 100;

        $receiptEmail = $order->address?->email ?: $order->user?->email;

        $pi = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
            ],
            'receipt_email' => $receiptEmail ?: null,
        ]);

        $order->update([
            'stripe_payment_intent_id' => $pi->id,
            'stripe_client_secret' => $pi->client_secret,
            'payment_status' => 'unpaid',
        ]);

        return [
            'id' => $pi->id,
            'client_secret' => $pi->client_secret,
            'status' => $pi->status,
        ];
    }
}
