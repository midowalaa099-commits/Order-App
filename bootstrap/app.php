<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'index.php/api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*', 'index.php/api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'You must be logged in to do that.',
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'message' => 'You are not allowed to do that.',
                ], 403);
            }

            if ($e instanceof ThrottleRequestsException) {
                return response()->json([
                    'message' => 'Too many requests. Please slow down and try again shortly.',
                ], 429);
            }

            if ($e instanceof ModelNotFoundException) {
                $model = class_basename($e->getModel());

                return response()->json([
                    'message' => "{$model} not found.",
                ], 404);
            }

            if (config('app.debug')) {
                return null;
            }

            return response()->json([
                'message' => 'Something went wrong on our end.',
            ], 500);
        });
    })->create();
