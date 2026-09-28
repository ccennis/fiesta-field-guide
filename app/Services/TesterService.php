<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The owner's view of the people testing. Removing someone's access keeps
 * their pieces and wishlist, blocks sign-in, and ends any session they have
 * open so the change takes effect at once.
 */
class TesterService extends BaseService
{
    /**
     * @return Collection<int, User>
     */
    public function list(): Collection
    {
        return User::where('role', UserRole::Tester)
            ->withCount('holdings')
            ->orderBy('name')
            ->get();
    }

    public function disable(User $tester): User
    {
        $this->assertTester($tester);

        $tester->forceFill(['disabled_at' => now(), 'remember_token' => null])->save();
        DB::table('sessions')->where('user_id', $tester->id)->delete();

        return $tester->loadCount('holdings');
    }

    public function enable(User $tester): User
    {
        $this->assertTester($tester);

        $tester->update(['disabled_at' => null]);

        return $tester->loadCount('holdings');
    }

    private function assertTester(User $user): void
    {
        if ($user->isOwner()) {
            throw new RuntimeException("The owner's access cannot be removed.");
        }
    }
}
