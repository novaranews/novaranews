<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline HTTP security headers for a public site.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Add only HSTS and nosniff to XML/plain-text responses such as sitemaps,
        // robots.txt, RSS, and ads.txt. HTML-only headers do not apply to them.
        $contentType = (string) $response->headers->get('Content-Type', '');
        $isHtml = str_contains($contentType, 'text/html') || $contentType === '';

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if ($isHtml) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');
            $response->headers->set('X-XSS-Protection', '0'); // Prefer CSP in modern browsers and disable the legacy header.

            // Content Security Policy substantially reduces XSS risk.
            // Vite requires unsafe-inline. Only admin pages (TinyMCE) receive unsafe-eval.
            // connect-src: GA4 often posts to region hosts (e.g. region1.analytics.google.com); list exact + wildcards per Google Tag CSP guidance.
            $isAdminPath = str_starts_with($request->path(), 'admin');
            $unsafeEval = $isAdminPath ? " 'unsafe-eval'" : '';
            $adsenseCompatiblePublicCsp = ! $isAdminPath;

            // Google documents AdSense as CSP-sensitive because injected ad domains can
            // change over time. Keep the broader sources scoped to public pages, but do not
            // read DB-backed settings from this global middleware; it must stay boot-safe.
            $scriptSrc = $adsenseCompatiblePublicCsp
                ? "script-src 'self' 'unsafe-inline'{$unsafeEval} https:"
                : "script-src 'self' 'unsafe-inline'{$unsafeEval} https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google-analytics.com https://pagead2.googlesyndication.com https://news.google.com";
            $connectSrc = $adsenseCompatiblePublicCsp
                ? "connect-src 'self' https:"
                : "connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://analytics.google.com https://*.analytics.google.com https://www.googletagmanager.com https://stats.g.doubleclick.net https://news.google.com";
            $frameSrc = $adsenseCompatiblePublicCsp
                ? "frame-src 'self' https:"
                : "frame-src 'self' https://googleads.g.doubleclick.net https://tpc.googlesyndication.com https://news.google.com";
            $csp = implode('; ', [
                "default-src 'self'",
                $scriptSrc,
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                "img-src 'self' data: blob: https: http:",
                "font-src 'self' data:",
                $connectSrc,
                $frameSrc,
                "media-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "upgrade-insecure-requests",
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($request->secure() || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
