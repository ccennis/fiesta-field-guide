<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The shared catalog and who may use the app are the admin's to change.
 * Members are refused here as well as having the screens hidden.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the admin can change the catalog or manage members.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
