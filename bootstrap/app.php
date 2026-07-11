<?php

use App\Http\Middleware\ResolveBrand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            ResolveBrand::class,
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'family.auth' => \App\Http\Middleware\FamilyAuth::class,
            'facility.auth' => \App\Http\Middleware\FacilityUserAuth::class,
            'track.visit' => \App\Http\Middleware\TrackSiteVisit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $e) {
            notify_admin_of_exception($e);
        });
    })->create();
