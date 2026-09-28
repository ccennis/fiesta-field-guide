<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of anyone whose access was removed, even mid-session, and
 * answers as if they had never signed in.
 */
class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDisabled()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => false,
                'message' => 'Your access has been removed.',
                'data' => null,
                'errors' => null,
            ], 401);
        }

        return $next($request);
    }
}
