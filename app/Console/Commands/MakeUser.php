<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Holding;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * There is no public sign-up page. The owner's login is created, or anyone's
 * password reset, from the terminal so the password is never typed anywhere
 * else. Testers join through invite links instead.
 */
class MakeUser extends Command
{
    protected $signature = 'fiesta:make-user {email} {--name=Owner}';

    protected $description = "Create the owner's login, or reset anyone's password if the email already exists";

    public function handle(): int
    {
        $email = $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $password = $this->secret('Password');

        if ($password === null || mb_strlen($password) < 8) {
            $this->error('Use at least 8 characters.');

            return self::FAILURE;
        }

        if ($this->secret('Password again') !== $password) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing !== null) {
            $existing->update(['password' => $password]);
            $this->info("Reset the password for {$email}.");

            return self::SUCCESS;
        }

        if (User::owner() !== null) {
            $this->error('The owner account already exists. Testers join with an invite link from the Testers screen.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $password) {
            $owner = User::create([
                'name' => $this->option('name'),
                'email' => $email,
                'password' => $password,
                'role' => UserRole::Owner,
            ]);

            // Pieces and wishes imported before any account existed are the owner's.
            Holding::whereNull('user_id')->update(['user_id' => $owner->id]);
            WishlistItem::whereNull('user_id')->update(['user_id' => $owner->id]);
        });

        $this->info("Created the owner login for {$email}.");

        return self::SUCCESS;
    }
}
