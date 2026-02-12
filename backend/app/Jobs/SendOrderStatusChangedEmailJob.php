<?php

namespace App\Jobs;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusChangedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function __construct(
        public int $orderId,
        public string $fromStatus,
        public string $toStatus
    ) {}

    public function handle(): void
    {
        $order = Order::query()
            ->with(['items', 'address', 'user'])
            ->findOrFail($this->orderId);

        $to = $order->address?->email ?: $order->user?->email;

        if (! $to) {
            return;
        }

        Mail::to($to)->send(
            new OrderStatusChangedMail($order, $this->fromStatus, $this->toStatus)
        );
    }
}
