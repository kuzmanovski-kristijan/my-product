<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function __construct(
        private GoogleServiceAccountToken $tokenService
    ) {}

    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $projectId = config('services.fcm.project_id');
        if (! $projectId) {
            Log::warning('FCM not configured: missing services.fcm.project_id');
            return;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();

        if (! $tokens) {
            return;
        }

        // FCM v1 requires 1 token per request (message.token)
        foreach ($tokens as $token) {
            $this->sendToToken($projectId, $token, $title, $body, $data, $userId);
        }
    }

    private function sendToToken(string $projectId, string $deviceToken, string $title, string $body, array $data, int $userId): void
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $accessToken = $this->tokenService->getAccessToken();

        // Data must be string:string for FCM
        $dataStrings = [];
        foreach ($data as $k => $v) {
            $dataStrings[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $dataStrings,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=UTF-8',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15,
        ]);

        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            Log::error('FCM send failed (curl)', [
                'user_id' => $userId,
                'token' => $this->mask($deviceToken),
                'error' => $err,
            ]);
            // throw to allow retry if job wants retries
            throw new \RuntimeException("FCM curl error: {$err}");
        }

        // Success
        if ($status >= 200 && $status < 300) {
            Log::info('FCM sent', [
                'user_id' => $userId,
                'token' => $this->mask($deviceToken),
            ]);
            return;
        }

        // Invalid token cleanup (common statuses: 400/404 with UNREGISTERED)
        $shouldDelete = $this->isUnregisteredToken($resp);

        Log::warning('FCM send non-2xx', [
            'status' => $status,
            'user_id' => $userId,
            'token' => $this->mask($deviceToken),
            'response' => $resp,
            'delete_token' => $shouldDelete,
        ]);

        if ($shouldDelete) {
            DeviceToken::query()->where('token', $deviceToken)->delete();
            return;
        }

        // non-unregistered errors -> throw for retry/backoff
        throw new \RuntimeException("FCM HTTP {$status}: {$resp}");
    }

    private function isUnregisteredToken(string $resp): bool
    {
        $data = json_decode($resp, true);
        if (! is_array($data)) {
            return false;
        }

        // Typical: error.details[0].errorCode = "UNREGISTERED"
        $details = $data['error']['details'] ?? null;
        if (! is_array($details)) {
            return false;
        }

        foreach ($details as $d) {
            if (($d['errorCode'] ?? null) === 'UNREGISTERED') {
                return true;
            }
        }

        return false;
    }

    private function mask(string $token): string
    {
        $len = strlen($token);
        if ($len <= 10) return '***';
        return substr($token, 0, 6) . '...' . substr($token, -4);
    }
}
