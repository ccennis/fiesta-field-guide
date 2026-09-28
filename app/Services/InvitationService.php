<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Single-use invite links. The token is shown once, when the link is made, and
 * only its hash is kept. A link lets exactly one person join as a member.
 */
class InvitationService extends BaseService
{
    private const DAYS_VALID = 7;

    /**
     * @return array{invitation: Invitation, link: string}
     */
    public function create(User $admin, ?string $note): array
    {
        $token = Str::random(40);

        $invitation = Invitation::create([
            'token_hash' => Invitation::hashToken($token),
            'note' => $note,
            'created_by' => $admin->id,
            'expires_at' => now()->addDays(self::DAYS_VALID),
        ]);

        return ['invitation' => $invitation, 'link' => url('/invite/'.$token)];
    }

    /**
     * @return Collection<int, Invitation>
     */
    public function open(): Collection
    {
        return Invitation::open()->orderByDesc('created_at')->get();
    }

    public function revoke(Invitation $invitation): void
    {
        $invitation->delete();
    }

    public function find(string $token): ?Invitation
    {
        return Invitation::open()->where('token_hash', Invitation::hashToken($token))->first();
    }

    /**
     * Create the member and sign them in. An invited friend may see the
     * admin's collection, and skips confirming their email, since the admin
     * sent the link to them directly. The row is locked while it is used, so a
     * link opened twice at once still makes only one account.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function accept(string $token, array $data, Request $request): User
    {
        $user = DB::transaction(function () use ($token, $data) {
            $invitation = Invitation::open()
                ->where('token_hash', Invitation::hashToken($token))
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                throw new RuntimeException('This invite link has expired or has already been used.');
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Member,
                'sees_admin_collection' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->update(['accepted_at' => now(), 'accepted_by' => $user->id]);

            return $user;
        });

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return $user;
    }
}
