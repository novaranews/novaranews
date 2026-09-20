<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => \App\Http\Middleware\SetLocale::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.locale' => \App\Http\Middleware\SetAdminLocale::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetPreferredLocale::class,
            \App\Http\Middleware\MarkdownNegotiation::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\CacheControl::class,
        ]);

        // Exclude /novara-csrf from CSRF validation.
        // It is a POST endpoint, so FastCGI and Cloudflare do not cache it; it returns its own token.
        $middleware->validateCsrfTokens(except: [
            'novara-csrf',
            'mcp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
