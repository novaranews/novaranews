<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleIndexingApiService
{
    private const SCOPE = 'https://www.googleapis.com/auth/indexing';

    private const PUBLISH_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    public function isFeatureEnabled(): bool
    {
        return (bool) config('services.google_indexing.enabled', false);
    }

    public function isConfigured(): bool
    {
        return $this->resolveKeyFile() !== null;
    }

    public function dailyLimit(): int
    {
        return max(1, (int) config('services.google_indexing.daily_limit', 180));
    }

    public function dailyUsed(): int
    {
        return (int) Cache::get($this->dailyCacheKey(), 0);
    }

    public function dailyRemaining(): int
    {
        return max(0, $this->dailyLimit() - $this->dailyUsed());
    }

    /**
     * @return array{ok: bool, message: string, http_status: ?int}
     */
    public function publishUrlUpdated(string $absoluteUrl): array
    {
        if ($this->dailyUsed() >= $this->dailyLimit()) {
            return ['ok' => false, 'message' => 'daily_limit', 'http_status' => null];
        }

        $absoluteUrl = trim($absoluteUrl);
        if ($absoluteUrl === '' || (! str_starts_with($absoluteUrl, 'http://') && ! str_starts_with($absoluteUrl, 'https://'))) {
            return ['ok' => false, 'message' => 'url_must_be_absolute_https', 'http_status' => null];
        }

        $keyFile = $this->resolveKeyFile();
        if ($keyFile === null) {
            return ['ok' => false, 'message' => 'not_configured', 'http_status' => null];
        }

        try {
            $creds = new ServiceAccountCredentials(self::SCOPE, $keyFile);
            $token = $creds->fetchAuthToken();
        } catch (Throwable $e) {
            Log::warning('google_indexing.auth', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'auth: '.$e->getMessage(), 'http_status' => null];
        }

        if (! is_array($token)) {
            return ['ok' => false, 'message' => 'invalid_token_response', 'http_status' => null];
        }

        $access = $token['access_token'] ?? null;
        if (! is_string($access) || $access === '') {
            $err = $token['error'] ?? $token['error_description'] ?? 'no_access_token';

            return ['ok' => false, 'message' => is_scalar($err) ? (string) $err : 'no_access_token', 'http_status' => null];
        }

        try {
            $response = Http::timeout(25)
                ->withToken($access)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::PUBLISH_URL, [
                    'url' => $absoluteUrl,
                    'type' => 'URL_UPDATED',
                ]);
        } catch (Throwable $e) {
            Log::warning('google_indexing.http', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'http: '.$e->getMessage(), 'http_status' => null];
        }

        if ($response->successful()) {
            $this->incrementDailySuccess();

            return ['ok' => true, 'message' => 'ok', 'http_status' => $response->status()];
        }

        $body = $response->json();
        $msg = is_array($body)
            ? ($body['error']['message'] ?? $body['error']['status'] ?? $response->body())
            : $response->body();

        return [
            'ok' => false,
            'message' => is_string($msg) ? $msg : $response->body(),
            'http_status' => $response->status(),
        ];
    }

    private function incrementDailySuccess(): void
    {
        $key = $this->dailyCacheKey();
        $next = $this->dailyUsed() + 1;
        Cache::put($key, $next, now('UTC')->startOfDay()->addDays(2));
    }

    private function dailyCacheKey(): string
    {
        return 'google_indexing:daily:'.now('UTC')->format('Y-m-d');
    }

    private function resolveKeyFile(): ?string
    {
        $configured = trim((string) config('services.google_indexing.credentials_path', ''));
        if ($configured === '') {
            return null;
        }

        $candidates = [$configured];
        if (! str_starts_with($configured, '/') && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $configured)) {
            $candidates[] = base_path($configured);
        }
        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
