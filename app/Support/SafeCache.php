<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class SafeCache
{
    public static function remember(string $key, mixed $ttl, Closure $callback): mixed
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (Throwable $e) {
            self::reportSilently($e);

            return $callback();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (Throwable $e) {
            self::reportSilently($e);

            return $default;
        }
    }

    public static function put(string $key, mixed $value, mixed $ttl = null): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (Throwable $e) {
            self::reportSilently($e);
        }
    }

    public static function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (Throwable $e) {
            self::reportSilently($e);
        }
    }

    private static function reportSilently(Throwable $e): void
    {
        try {
            report($e);
        } catch (Throwable) {
            //
        }
    }
}
