<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'admin:create-user
        {--email=admin@nasande.cg : Email admin}
        {--name=Administrateur : Nom}
        {--password= : Mot de passe (sinon admin12345)}';

    protected $description = 'Crée ou met à jour un compte administrateur';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');
        $password = (string) ($this->option('password') ?: 'admin12345');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $this->info("Admin prêt : {$user->email} (role: {$user->role})");
        if (! $this->option('password')) {
            $this->warn('Mot de passe par défaut : admin12345 — change-le en production.');
        }

        return self::SUCCESS;
    }
}
