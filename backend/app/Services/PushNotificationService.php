<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();

        if (! $tokens) {
            return;
        }

        // MVP hook: log it (later replace with FCM/APNs client)
        Log::info('PUSH_HOOK', [
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'tokens_count' => count($tokens),
        ]);
    }
}
