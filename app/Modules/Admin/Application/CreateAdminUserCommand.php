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
        {--password= : Mot de passe (obligatoire)}';

    protected $description = 'Crée ou met à jour un compte administrateur';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');
        $password = (string) $this->option('password');
        if ($password === '') {
            $this->error('Option --password obligatoire (pas de mot de passe par défaut).');

            return self::FAILURE;
        }

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

        return self::SUCCESS;
    }
}
