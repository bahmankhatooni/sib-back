<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // تنظیم Sanctum برای API
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        // تنظیمات CORS
        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // ثبت middlewareهای سفارشی
        $middleware->alias([
            'check.role' => \App\Http\Middleware\CheckUserRole::class,
        ]);

        // غیرفعال کردن CSRF برای مسیرهای api
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/csrf-cookie',
        ]);
    })
    ->withProviders([
        App\Providers\AuthServiceProvider::class,  // اضافه کردن AuthServiceProvider
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
