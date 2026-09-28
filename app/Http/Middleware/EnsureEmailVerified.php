<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Someone who signed up can sign in before confirming their email, but can
 * do nothing else until they do. Answers in the API's usual shape.
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Confirm your email address first. The link is in the email we sent you.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
