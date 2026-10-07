<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('daydreaming:admin {email} {--revoke}')]
#[Description('Grant or revoke Daydreaming admin access for an existing user')]
class GrantAdminAccess extends Command
{
    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $isAdmin = ! $this->option('revoke');
        $allowedEmails = array_map(strtolower(...), config('services.admin.emails'));

        if ($isAdmin && (! str_ends_with($email, '@spatie.be') || ! in_array($email, $allowedEmails, true))) {
            $this->error('Only @spatie.be addresses in ADMIN_EMAILS can become admins.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('Create this user with make:filament-user first.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => $isAdmin])->save();
        $this->info($isAdmin ? 'Admin access granted.' : 'Admin access revoked.');

        return self::SUCCESS;
    }
}
