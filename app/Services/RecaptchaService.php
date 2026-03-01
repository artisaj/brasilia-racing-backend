<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public function validateToken(string $token, ?string $ip = null): bool
    {
        $enabled = (bool) config('services.recaptcha.enabled', false);

        if (! $enabled) {
            return true;
        }

        $secret = (string) config('services.recaptcha.secret_key');

        if ($secret === '') {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($ip !== null && $ip !== '') {
            $payload['remoteip'] = $ip;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post((string) config('services.recaptcha.verify_url'), $payload);

        if (! $response->ok()) {
            return false;
        }

        return (bool) data_get($response->json(), 'success', false);
    }
}
