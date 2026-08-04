<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

// Bootstraps the first admin account: the admin area itself has no way to
// create its own first admin, since every admin endpoint requires an admin
// to already be authenticated.
class PromoteAdmin extends Command
{
    protected $signature = 'admin:promote {email}';

    protected $description = 'Grant admin access to an existing user by email';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->info("{$user->email} is already an admin.");

            return self::SUCCESS;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
