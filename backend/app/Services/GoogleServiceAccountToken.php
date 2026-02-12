<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;

class GoogleServiceAccountToken
{
    /**
     * Returns OAuth2 access token for Firebase scope (cached).
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'fcm_access_token';

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $path = config('services.fcm.service_account_path');
            if (! $path || ! File::exists(base_path($path))) {
                throw new RuntimeException('FCM service account JSON not found at: ' . base_path($path));
            }

            $json = json_decode(File::get(base_path($path)), true);
            if (! is_array($json)) {
                throw new RuntimeException('Invalid FCM service account JSON.');
            }

            $clientEmail = $json['client_email'] ?? null;
            $privateKey = $json['private_key'] ?? null;
            if (! $clientEmail || ! $privateKey) {
                throw new RuntimeException('Missing client_email/private_key in service account JSON.');
            }

            $now = time();
            $aud = 'https://oauth2.googleapis.com/token';
            $scope = 'https://www.googleapis.com/auth/firebase.messaging';

            $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtClaims = $this->base64UrlEncode(json_encode([
                'iss' => $clientEmail,
                'scope' => $scope,
                'aud' => $aud,
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $toSign = $jwtHeader . '.' . $jwtClaims;

            $signature = '';
            $ok = openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            if (! $ok) {
                throw new RuntimeException('Failed to sign JWT for FCM.');
            }

            $jwt = $toSign . '.' . $this->base64UrlEncode($signature);

            $ch = curl_init($aud);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]),
                CURLOPT_TIMEOUT => 15,
            ]);

            $resp = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($resp === false) {
                throw new RuntimeException("FCM OAuth token request failed: {$err}");
            }

            $data = json_decode($resp, true);
            if ($status < 200 || $status >= 300 || ! is_array($data) || empty($data['access_token'])) {
                throw new RuntimeException("FCM OAuth token error ({$status}): " . $resp);
            }

            return $data['access_token'];
        });
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
