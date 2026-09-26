<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * There is no sign-up page. The owner's login is created, or its password
 * reset, from the terminal so the password is never typed anywhere else.
 */
class MakeUser extends Command
{
    protected $signature = 'fiesta:make-user {email} {--name=Owner}';

    protected $description = 'Create the login, or reset its password if the email already exists';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $password = $this->secret('Password');

        if ($password === null || mb_strlen($password) < 12) {
            $this->error('Use at least 12 characters.');

            return self::FAILURE;
        }

        if ($this->secret('Password again') !== $password) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $this->option('name'), 'password' => $password],
        );

        $this->info($user->wasRecentlyCreated ? "Created a login for {$email}." : "Reset the password for {$email}.");

        return self::SUCCESS;
    }
}
