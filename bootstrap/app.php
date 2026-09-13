<?php

use App\Http\Middleware\CheckAdminAccess;
use App\Http\Middleware\CheckGroupLeaderRole;
use App\Http\Middleware\CheckPastorRole;
use App\Http\Middleware\CheckStudentRole;
use App\Http\Middleware\CheckTeacherRole;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Providers\EventServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withProviders([
        EventServiceProvider::class, // ← ДОБАВИТЬ ЭТУ СТРОКУ
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'admin.access' => CheckAdminAccess::class,
            'role.teacher' => CheckTeacherRole::class,
            'role.student' => CheckStudentRole::class,
            'role.group_leader' => CheckGroupLeaderRole::class,
            'role.pastor' => CheckPastorRole::class,
        ]);

        $middleware->api([
            SubstituteBindings::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated. Please provide a valid Sanctum token.',
                ], 401);
            }

            return redirect()->guest('/admin/login');
        });
    })
    ->create();
