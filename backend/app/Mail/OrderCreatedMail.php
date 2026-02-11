<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['items', 'address']);
    }

    public function build(): self
    {
        return $this
            ->subject("Потврда за нарачка: {$this->order->order_number}")
            ->markdown('mail.orders.created', [
                'order' => $this->order,
            ]);
    }
}
