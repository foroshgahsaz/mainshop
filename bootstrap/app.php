<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'payment/callback',
            'payment/callback/tara',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Windows / junction paths: realpath() may fail namespace auto-detection
try {
    $app->getNamespace();
} catch (RuntimeException) {
    $property = new ReflectionProperty(Application::class, 'namespace');
    $property->setAccessible(true);
    $property->setValue($app, 'App\\');
}

return $app;
