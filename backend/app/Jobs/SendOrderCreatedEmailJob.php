<?php

namespace App\Jobs;

use App\Mail\OrderCreatedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderCreatedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->with(['items', 'address'])->findOrFail($this->orderId);

        // Guest checkout: use address email, fallback to user email (if any)
        $to = $order->address?->email ?: $order->user?->email;

        if (! $to) {
            return; // no email provided
        }

        Mail::to($to)->send(new OrderCreatedMail($order));
    }
}
