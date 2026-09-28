<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Session login for the single owner. Sign-ins are always remembered, since
 * the app lives on a phone home screen and should not ask again in a shop.
 */
class AuthService extends BaseService
{
    public function login(Request $request, array $credentials): ?User
    {
        // A tester whose access was removed cannot sign in again.
        if (! Auth::attempt($credentials + ['disabled_at' => null], remember: true)) {
            return null;
        }

        $request->session()->regenerate();

        return Auth::user();
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
