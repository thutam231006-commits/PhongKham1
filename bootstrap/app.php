<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class]);

        // API dùng chung phiên đăng nhập của web (dự án chưa cài Sanctum):
        // thêm cookie + session + CSRF vào nhóm api để API xác thực được
        // bằng cookie phiên và vẫn được bảo vệ khỏi CSRF.
        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
        ]);

        $middleware->append(SecurityHeaders::class);
        $middleware->validateCsrfTokens(except: ['webhooks/sepay']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->expectsJson() || ! in_array($exception->getStatusCode(), [409, 422], true)) {
                return null;
            }

            return back()->withInput()->with('error', $exception->getMessage() ?: 'Thao tác không thể thực hiện ở trạng thái hiện tại.');
        });
    })->create();
