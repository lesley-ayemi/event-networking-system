<?php

use App\Exceptions\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Every API error — whether an intentional ApiException or a
        // framework exception (validation, auth, not-found, ...) — renders
        // through this one envelope: {success: false, message, errorCode}.
        $envelope = fn (string $message, string $errorCode, int $status, array $extra = []) => response()->json([
            'success' => false,
            'message' => $message,
            'errorCode' => $errorCode,
            ...$extra,
        ], $status);

        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (ApiException $e, Request $request) use ($envelope) {
            return $envelope($e->getMessage(), $e->errorCode, $e->status);
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return $envelope(
                $e->getMessage() ?: 'Please check the highlighted fields and try again.',
                'VALIDATION_ERROR',
                $e->status,
                ['errors' => $e->errors()],
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return $envelope('Your session has expired. Please log in again.', 'UNAUTHENTICATED', 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return $envelope('You do not have permission to do that.', 'FORBIDDEN', 403);
        });

        // Catches everything else with a real HTTP status: abort_if(...) calls,
        // 404s from unmatched routes, 405s, etc. Laravel converts a thrown
        // ModelNotFoundException into a NotFoundHttpException (preserving it
        // as ->getPrevious()) before any renderer ever sees it, so that's
        // detected here rather than via its own ModelNotFoundException
        // renderer, which would never actually be reached.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $status = $e->getStatusCode();

            if ($status === 404 && $e->getPrevious() instanceof ModelNotFoundException) {
                $model = Str::upper(Str::snake(class_basename($e->getPrevious()->getModel())));

                return $envelope("We couldn't find what you were looking for.", "{$model}_NOT_FOUND", 404);
            }

            [$default, $errorCode] = match (true) {
                $status === 404 => ["We couldn't find what you were looking for.", 'NOT_FOUND'],
                $status === 403 => ['You do not have permission to do that.', 'FORBIDDEN'],
                $status === 405 => ["That action isn't available.", 'METHOD_NOT_ALLOWED'],
                $status === 429 => ["You're doing that a bit too fast. Please wait a moment and try again.", 'TOO_MANY_REQUESTS'],
                default => ["We couldn't complete that action. Your information has not been lost. Please try again.", 'REQUEST_FAILED'],
            };

            return $envelope($e->getMessage() ?: $default, $errorCode, $status);
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($envelope, $isApi) {
            if (! $isApi($request)) {
                return null;
            }

            report($e);

            return $envelope(
                "We couldn't complete that action. Your information has not been lost. Please try again.",
                'SERVER_ERROR',
                500,
            );
        });
    })->create();
