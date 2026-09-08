<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventPageCaching;
use App\Http\Middleware\SetCurrentBranch;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['locale', 'branch_id']);

        $middleware->web(append: [
            SetCurrentBranch::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            PreventPageCaching::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
