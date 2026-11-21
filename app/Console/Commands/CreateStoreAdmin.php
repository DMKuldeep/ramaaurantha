<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateStoreAdmin extends Command
{
    protected $signature = 'store:admin {email} {--name=}';

    protected $description = 'Create or promote an administrator account';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        $password = $this->secret('Administrator password (minimum 12 characters)');
        if (! is_string($password) || strlen($password) < 12) {
            $this->error('The administrator password must contain at least 12 characters.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $this->option('name') ?: ($user->name ?: 'Store owner');
        $user->role = 'admin';
        $user->password = Hash::make($password);
        $user->email_verified_at ??= now();
        $user->save();

        $this->info("Administrator account ready for {$email}.");

        return self::SUCCESS;
    }
}
