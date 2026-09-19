<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');
        $allowed = config('novaranews.locales', ['en']);

        if (! is_string($locale) || ! in_array($locale, $allowed, true)) {
            abort(404);
        }

        app()->setLocale($locale);

        $response = $next($request);
        $response->withCookie(cookie()->forever('site_locale', $locale));

        return $response;
    }
}
