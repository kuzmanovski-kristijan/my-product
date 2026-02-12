<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $fromStatus,
        public string $toStatus
    ) {
        $this->order->loadMissing(['items', 'address']);
    }

    public function build(): self
    {
        return $this
            ->subject("Статус на нарачка {$this->order->order_number}: {$this->toStatus}")
            ->markdown('mail.orders.status-changed', [
                'order' => $this->order,
                'fromStatus' => $this->fromStatus,
                'toStatus' => $this->toStatus,
            ]);
    }
}
