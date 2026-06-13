<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('quiniela:admin {email} {password}')]
#[Description('Create or update a super_admin user')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make($password),
                'role'     => 'super_admin',
            ]
        );

        $verb = $user->wasRecentlyCreated ? 'Created' : 'Updated';
        $this->info("{$verb} super_admin: {$email}");

        return self::SUCCESS;
    }
}
