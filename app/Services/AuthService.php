<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Session sign-in, sign-up, email confirmation and password reset. Sign-ins are always
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

    /**
     * Email a reset link. Nothing is said about whether the address has an
     * account, and a member whose access was removed is not sent one.
     */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email, 'disabled_at' => null]);
    }

    /**
     * Set a new password from an emailed link. Opening the link proves the
     * address, so it also counts as confirming it. Every other session and
     * remembered sign-in ends.
     *
     * @param  array{token: string, email: string, password: string}  $data
     */
    public function resetPassword(array $data): bool
    {
        $status = Password::reset($data + ['disabled_at' => null], function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        return $status === Password::PASSWORD_RESET;
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
