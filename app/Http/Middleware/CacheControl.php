<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP Cache-Control policy.
     *
 * Three scenarios:
 *  1. Admin/auth routes       → no-store, private (never cache)
 *  2. Authenticated user      → private, no-store + novara_auth cookie (Nginx bypass)
 *  3. Anonymous GET request   → public, s-maxage=300 (Cloudflare + FastCGI cache)
 *
 * The Nginx FastCGI cache map checks the novara_auth cookie in $http_cookie.
 * The cookie is set only when auth()->check() is true.
 */
class CacheControl
{
    /** Cloudflare/FastCGI edge TTL in seconds. */
    private const EDGE_TTL = 300;   // 5 minutes

    /** Browser cache TTL in seconds. */
    private const BROWSER_TTL = 120; // 2 minutes

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Apply only to GET/HEAD; leave POST/PUT/DELETE/PATCH responses untouched.
        if (! $request->isMethodSafe()) {
            return $response;
        }

        // Do not cache 4xx/5xx error responses.
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        // ── Admin routes ──────────────────────────────────────────────────────
        if ($request->is('admin') || $request->is('admin/*')) {
            return $this->noCache($response);
        }

        // ── Authentication routes ──────────────────────────────────────────────
        if ($this->isAuthPath($request)) {
            return $this->noCache($response);
        }

        // ── Authenticated user ─────────────────────────────────────────────────
        if ($this->hasSessionCookie($request) && auth()->check()) {
            return $this->privateCache($response, $request);
        }

        // ── Anonymous visitor: allow edge caching ──────────────────────────────
        $this->publicCache($response);

        // Remove an authentication marker that may remain after logout.
        if ($request->cookie('novara_auth')) {
            $response->headers->clearCookie('novara_auth', '/', null, $request->secure(), true);
        }

        return $response;
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Authentication/session paths that must bypass caching.
     * Includes login, registration, profile, dashboard, and password reset flows.
     */
    private function isAuthPath(Request $request): bool
    {
        return $request->is(
            'login',
            'register',
            'logout',
            'two-factor',
            'two-factor/*',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'verify-email',
            'verify-email/*',
            'email/verification-notification',
            'confirm-password',
            'password',
            'profile',
            'profile/*',
            'dashboard',
        );
    }

    private function hasSessionCookie(Request $request): bool
    {
        $sessionCookie = (string) config('session.cookie', 'laravel_session');

        return ($sessionCookie !== '' && $request->cookies->has($sessionCookie))
            || $request->cookies->has('novara_auth')
            || $request->cookies->has('remember_web_'.sha1(config('app.key', '')));
    }

    /** Never cache. */
    private function noCache(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->remove('Pragma');

        return $response;
    }

    /**
     * Authenticated user: private response plus a novara_auth cookie.
     * The Nginx FastCGI cache map uses the cookie to decide whether to bypass cache.
     * HttpOnly prevents JavaScript access; Nginx reads it from the raw Cookie header.
     */
    private function privateCache(Response $response, Request $request): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->remove('Pragma');

        $response->headers->setCookie(Cookie::create(
            name    : 'novara_auth',
            value   : '1',
            expire  : 0,                   // Session cookie removed when the browser closes.
            path    : '/',
            secure  : $request->secure(),
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        ));

        return $response;
    }

    /**
     * Anonymous visitor: cacheable by Cloudflare and FastCGI.
     * stale-while-revalidate lets Cloudflare refresh in the background after TTL expiry.
 *
     * Set-Cookie is removed at the Nginx layer with fastcgi_hide_header Set-Cookie.
     * Anonymous page views do not need a session. app.js refreshes form CSRF tokens
     * from POST /novara-csrf through refreshCsrfIfNeeded().
     */
    private function publicCache(Response $response): void
    {
        $response->headers->set('Cache-Control', sprintf(
            'public, max-age=%d, s-maxage=%d, stale-while-revalidate=60',
            self::BROWSER_TTL,
            self::EDGE_TTL,
        ));
        $response->headers->remove('Pragma');
    }
}
