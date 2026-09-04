<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Application;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CustomerIdentityService
{
    public function findOrCreate(string $email, string $name): User
    {
        $email = strtolower(trim($email));
        $name = trim($name);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : Str::before($email, '@'),
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
        );

        if ($name !== '' && $user->name !== $name) {
            $user->name = $name;
            $user->save();
        }

        return $user;
    }
}
