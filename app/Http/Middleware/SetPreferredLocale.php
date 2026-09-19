<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPreferredLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('novaranews.locales', ['en']);
        $default = (string) config('novaranews.default_locale', 'en');
        $cookieLocale = $request->cookie('site_locale');

        if (is_string($cookieLocale) && in_array($cookieLocale, $allowed, true)) {
            app()->setLocale($cookieLocale);
        } elseif (in_array($default, $allowed, true)) {
            app()->setLocale($default);
        } else {
            app()->setLocale('en');
        }

        return $next($request);
    }
}
