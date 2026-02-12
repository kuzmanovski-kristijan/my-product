<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderStatusChangedEmailJob;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Handle event types
        $type = $event->type ?? '';
        $object = $event->data->object ?? null;

        if (! $object || empty($object->id)) {
            return response()->json(['ok' => true]);
        }

        $piId = (string) $object->id;

        $order = Order::query()->where('stripe_payment_intent_id', $piId)->first();

        // Fallback by metadata order_id/order_number (if needed)
        if (! $order && isset($object->metadata->order_id)) {
            $order = Order::query()->where('id', (int) $object->metadata->order_id)->first();
        }

        if (! $order) {
            Log::info('Stripe webhook: order not found', ['type' => $type, 'pi' => $piId]);
            return response()->json(['ok' => true]);
        }

        // Idempotency: if already paid, ignore duplicates
        if ($type === 'payment_intent.succeeded') {
            if ($order->payment_status !== 'paid') {
                $fromStatus = $order->status;

                $order->update([
                    'payment_status' => 'paid',
                    // MVP choice: auto-confirm after successful payment
                    'status' => $order->status === 'new' ? 'confirmed' : $order->status,
                ]);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'from_status' => $fromStatus,
                    'to_status' => $order->status,
                    'changed_by_user_id' => null,
                    'note' => 'Stripe payment succeeded',
                ]);

                // Optional: send status-changed email for ALL statuses (you already have job)
                SendOrderStatusChangedEmailJob::dispatch($order->id, $fromStatus, $order->status);

                // Optional: push is already hooked in admin status changes;
                // for webhook we can also push if user exists:
                if ($order->user_id) {
                    \App\Jobs\SendPushToUserJob::dispatch(
                        $order->user_id,
                        'Плаќање успешно',
                        "Нарачката {$order->order_number} е платена.",
                        ['order_number' => $order->order_number, 'payment_status' => 'paid']
                    );
                }
            }
        }

        if ($type === 'payment_intent.payment_failed') {
            if ($order->payment_status !== 'failed') {
                $order->update(['payment_status' => 'failed']);

                if ($order->user_id) {
                    \App\Jobs\SendPushToUserJob::dispatch(
                        $order->user_id,
                        'Плаќање неуспешно',
                        "Плаќањето за нарачката {$order->order_number} не успеа.",
                        ['order_number' => $order->order_number, 'payment_status' => 'failed']
                    );
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
