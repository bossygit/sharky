<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateVinsAdmin extends Command
{
    protected $signature = 'vins:admin
                            {email : Adresse email du compte}
                            {--name=Admin Nasande Vins : Nom affiché}
                            {--password= : Mot de passe (généré si absent)}
                            {--force : Réinitialise le mot de passe d’un compte existant}';

    protected $description = 'Crée (ou promeut) le compte administrateur de la boutique Nasande Vins.';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse email invalide.');

            return self::FAILURE;
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user && ! $this->option('force')) {
            if ($user->role !== 'admin') {
                $user->forceFill(['role' => 'admin'])->save();
                $this->info("Rôle admin accordé à {$email}.");

                return self::SUCCESS;
            }

            $this->info("Administrateur déjà en place : {$email} (aucune modification).");

            return self::SUCCESS;
        }

        $password = (string) ($this->option('password') ?: Str::password(18));

        $attributes = [
            'name' => (string) $this->option('name'),
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ];

        if ($user) {
            $user->forceFill($attributes)->save();
        } else {
            $user = new User;
            $user->forceFill(['email' => $email] + $attributes)->save();
        }

        $this->info("Administrateur prêt : {$email}");

        if (! $this->option('password')) {
            $this->line("Mot de passe généré : {$password}");
        }

        return self::SUCCESS;
    }
}
