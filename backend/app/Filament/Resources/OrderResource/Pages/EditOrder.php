<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\OrderStatusHistory;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changeStatus')
                ->label('Change status')
                ->form([
                    Select::make('status')
                        ->options([
                            'new' => 'new',
                            'confirmed' => 'confirmed',
                            'packed' => 'packed',
                            'shipped' => 'shipped',
                            'delivered' => 'delivered',
                            'canceled' => 'canceled',
                        ])
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('note')->maxLength(2000),
                ])
                ->action(function (array $data) {
                    $order = $this->record;
                    $from = $order->status;
                    $to = $data['status'];

                    if ($from === $to) {
                        Notification::make()->title('Status unchanged')->warning()->send();

                        return;
                    }

                    $order->update(['status' => $to]);

                    OrderStatusHistory::query()->create([
                        'order_id' => $order->id,
                        'from_status' => $from,
                        'to_status' => $to,
                        'changed_by_user_id' => auth()->id(),
                        'note' => $data['note'] ?? null,
                    ]);

                    \App\Jobs\SendOrderStatusChangedEmailJob::dispatch(
                        $order->id,
                        $from,
                        $to
                    );

                    if ($order->user_id) {
                        \App\Jobs\SendPushToUserJob::dispatch(
                            $order->user_id,
                            'Статус на нарачка',
                            "Нарачката {$order->order_number} е: {$to}",
                            ['order_number' => $order->order_number, 'status' => $to]
                        );
                    }

                    Notification::make()->title("Status changed: {$from} → {$to}")->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
