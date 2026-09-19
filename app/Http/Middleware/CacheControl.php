<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP Cache-Control yönetimi.
 *
 * Üç senaryo:
 *  1. Admin / auth rotaları      → no-store, private  (asla cache'leme)
 *  2. Giriş yapmış kullanıcı    → private, no-store  + novara_auth cookie (nginx bypass)
 *  3. Anonim GET isteği         → public, s-maxage=300 (Cloudflare + FastCGI cache)
 *
 * nginx FastCGI cache map'i $http_cookie içindeki "novara_auth" cookie'ye bakar;
 * bu cookie yalnızca auth()->check() === true olan yanıtlarda set edilir.
 */
class CacheControl
{
    /** Cloudflare / FastCGI edge TTL (saniye) */
    private const EDGE_TTL = 300;   // 5 dakika

    /** Tarayıcı cache TTL (saniye) */
    private const BROWSER_TTL = 120; // 2 dakika

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Yalnızca GET/HEAD — POST/PUT/DELETE/PATCH'e dokunma
        if (! $request->isMethodSafe()) {
            return $response;
        }

        // 4xx / 5xx yanıtlara dokunma (hata sayfalarını cache'leme)
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        // ── Admin rotaları ────────────────────────────────────────────────────
        if ($request->is('admin') || $request->is('admin/*')) {
            return $this->noCache($response);
        }

        // ── Kimlik doğrulama rotaları ─────────────────────────────────────────
        if ($this->isAuthPath($request)) {
            return $this->noCache($response);
        }

        // ── Giriş yapmış kullanıcı ────────────────────────────────────────────
        if ($this->hasSessionCookie($request) && auth()->check()) {
            return $this->privateCache($response, $request);
        }

        // ── Anonim ziyaretçi — edge cache'e izin ver ─────────────────────────
        $this->publicCache($response);

        // Çıkış sonrası kalmış olabilecek auth cookie'yi temizle
        if ($request->cookie('novara_auth')) {
            $response->headers->clearCookie('novara_auth', '/', null, $request->secure(), true);
        }

        return $response;
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kimlik doğrulama / oturum yolları — cache dışı.
     * (login, register, profile, dashboard, şifre sıfırlama...)
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

    /** Asla cache'leme */
    private function noCache(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->remove('Pragma');

        return $response;
    }

    /**
     * Giriş yapmış kullanıcı: private + novara_auth cookie.
     * Cookie, nginx FastCGI cache map'inin bypass kararı için kullanılır.
     * HttpOnly=true → JS erişimi engellenir; nginx raw Cookie header'dan okur.
     */
    private function privateCache(Response $response, Request $request): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->remove('Pragma');

        $response->headers->setCookie(Cookie::create(
            name    : 'novara_auth',
            value   : '1',
            expire  : 0,                   // session cookie — tarayıcı kapanınca silinir
            path    : '/',
            secure  : $request->secure(),
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        ));

        return $response;
    }

    /**
     * Anonim ziyaretçi: Cloudflare ve FastCGI tarafından cache'lenebilir.
     * stale-while-revalidate → TTL dolunca Cloudflare arka planda yeniler,
     * kullanıcı beklemiş hissetmez.
     *
     * Set-Cookie nginx katmanında kaldırılır (fastcgi_hide_header Set-Cookie).
     * Anonim sayfa görüntülemesi için session gerekmez; formlardaki CSRF token
     * app.js → refreshCsrfIfNeeded() ile POST /novara-csrf'den yenileniyor.
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
