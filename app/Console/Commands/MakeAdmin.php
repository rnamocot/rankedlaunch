<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Give an existing account admin access. Sign up on the site first, then:
 *   php artisan rankedlaunch:make-admin you@example.com
 */
#[Signature('rankedlaunch:make-admin {email : Email of an account that already signed up}')]
#[Description('Give an existing account admin access')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account with that email. Sign up on the site first, then run this again.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();
        ActivityLog::record('system', "{$user->name} was made an admin from the command line", user: $user);

        $this->info("{$user->name} is now an admin. Open /admin.");

        return self::SUCCESS;
    }
}
