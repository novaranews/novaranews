<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('novaranews.locales', ['en']);
        $queryLocale = $request->query('admin_locale');
        $sessionLocale = $request->session()->get('admin_locale');
        $defaultLocale = config('novaranews.default_locale', 'en');
        $authUser = Auth::user();
        $user = $authUser instanceof User ? $authUser : null;
        $userLocale = $user?->admin_locale;

        $selected = null;
        if (is_string($queryLocale) && in_array($queryLocale, $allowed, true)) {
            $selected = $queryLocale;
            $request->session()->put('admin_locale', $queryLocale);
            if ($user && $user->admin_locale !== $queryLocale) {
                $user->forceFill(['admin_locale' => $queryLocale])->save();
            }
        } elseif (is_string($userLocale) && in_array($userLocale, $allowed, true)) {
            $selected = $userLocale;
            $request->session()->put('admin_locale', $userLocale);
        } elseif (is_string($sessionLocale) && in_array($sessionLocale, $allowed, true)) {
            $selected = $sessionLocale;
        } elseif (in_array($defaultLocale, $allowed, true)) {
            $selected = $defaultLocale;
            $request->session()->put('admin_locale', $defaultLocale);
        } else {
            $selected = 'en';
            $request->session()->put('admin_locale', 'en');
        }

        app()->setLocale($selected);

        return $next($request);
    }
}
