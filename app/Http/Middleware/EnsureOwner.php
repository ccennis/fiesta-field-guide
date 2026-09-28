<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The shared catalog and the invitations are the owner's to change. Testers
 * are refused here as well as having the screens hidden.
 */
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can change the catalog or invite people.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
