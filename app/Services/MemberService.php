<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The admin's view of everyone else. Removing someone's access keeps their
 * pieces and wishlist, blocks sign-in, and ends any session they have open so
 * the change takes effect at once.
 */
class MemberService extends BaseService
{
    /**
     * @return Collection<int, User>
     */
    public function list(): Collection
    {
        return User::where('role', UserRole::Member)
            ->withCount('holdings')
            ->orderByDesc('created_at')
            ->get();
    }

    public function disable(User $member): User
    {
        $this->assertMember($member);

        $member->forceFill(['disabled_at' => now(), 'remember_token' => null])->save();
        DB::table('sessions')->where('user_id', $member->id)->delete();

        return $member->loadCount('holdings');
    }

    public function enable(User $member): User
    {
        $this->assertMember($member);

        $member->update(['disabled_at' => null]);

        return $member->loadCount('holdings');
    }

    private function assertMember(User $user): void
    {
        if ($user->isAdmin()) {
            throw new RuntimeException("The admin's access cannot be removed.");
        }
    }
}
