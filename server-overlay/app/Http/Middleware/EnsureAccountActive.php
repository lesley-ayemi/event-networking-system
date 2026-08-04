<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_suspended) {
            throw new ApiException(
                'Your account has been suspended. Contact support if you believe this is a mistake.',
                'ACCOUNT_SUSPENDED',
                403,
            );
        }

        return $next($request);
    }
}
