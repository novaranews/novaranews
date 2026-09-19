<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaVerifier
{
    public function verify(Request $request, string $expectedAction): bool
    {
        $siteKey = trim((string) config('novaranews.recaptcha.site_key', ''));
        $secretKey = trim((string) config('novaranews.recaptcha.secret_key', ''));

        if ($siteKey === '' && $secretKey === '') {
            return true;
        }

        $token = trim((string) $request->input('g-recaptcha-response', ''));
        if ($secretKey === '' || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification request failed: '.$e->getMessage());

            return false;
        }

        if (! $response->ok()) {
            return false;
        }

        $result = $response->json();
        if (! is_array($result) || ! ($result['success'] ?? false)) {
            return false;
        }

        $action = $result['action'] ?? null;
        if (is_string($action) && $action !== '' && $action !== $expectedAction) {
            return false;
        }

        $score = (float) ($result['score'] ?? 0);
        $threshold = (float) config('novaranews.recaptcha.score_threshold', 0.7);

        return $score >= $threshold;
    }
}
