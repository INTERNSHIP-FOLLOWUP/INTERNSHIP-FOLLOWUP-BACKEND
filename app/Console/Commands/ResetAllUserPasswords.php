<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAllUserPasswords extends Command
{
    protected $signature = 'users:reset-password {password=12345678}';
    protected $description = 'Reset all users password to a default value';

    public function handle()
    {
        $password = $this->argument('password');
        $count = User::withTrashed()->count();

        if (!$this->confirm("Reset password for all {$count} users to '{$password}'?")) {
            return;
        }

        User::withTrashed()->each(function ($user) use ($password) {
            $user->password = Hash::make($password);
            $user->save();
        });

        $this->info("Password reset to '{$password}' for all {$count} users.");
    }
}
