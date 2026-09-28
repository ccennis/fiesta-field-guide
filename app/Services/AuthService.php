<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Session sign-in, sign-up and email confirmation. Sign-ins are always
 * remembered, since the app lives on a phone home screen and should not ask
 * again in a shop.
 */
class AuthService extends BaseService
{
    public function login(Request $request, array $credentials): ?User
    {
        // A member whose access was removed cannot sign in again.
        if (! Auth::attempt($credentials + ['disabled_at' => null], remember: true)) {
            return null;
        }

        $request->session()->regenerate();

        return Auth::user();
    }

    /**
     * Anyone may sign up as a member. They are signed in straight away but can
     * do nothing until they confirm their email, and they do not see the
     * admin's collection; that is for friends who were invited.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(Request $request, array $data): User
    {
        $user = User::create($data + ['role' => UserRole::Member]);

        $user->sendEmailVerificationNotification();

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return $user;
    }

    /**
     * The link in the confirmation email. It is signed, so it works on a phone
     * that is not signed in. The hash ties it to the address it was sent to.
     */
    public function verifyEmail(int $userId, string $hash): bool
    {
        $user = User::find($userId);

        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return true;
    }

    public function resendVerification(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
