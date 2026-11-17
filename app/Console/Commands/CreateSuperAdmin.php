<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Crée (ou promeut) le compte Super Admin du propriétaire de la plateforme.
 *
 *   php artisan app:create-super-admin "+225 07 00 00 00 00" --first=Prénom --last=Nom
 *
 * Le mot de passe est demandé de façon masquée (ou via --password, pour l'automatisation).
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin
                            {phone : Numéro de téléphone utilisé pour se connecter}
                            {--first=Super : Prénom}
                            {--last=Admin : Nom}
                            {--password= : Mot de passe (sinon demandé)}';

    protected $description = 'Crée ou promeut le compte Super Admin de la plateforme';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Mot de passe (12 caractères minimum)');

        if (! is_string($password) || strlen($password) < 12) {
            $this->error('Le mot de passe doit contenir au moins 12 caractères.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['phone' => $this->argument('phone')]);

        // forceFill : is_platform_admin n'est pas assignable en masse
        $user->forceFill([
            'first_name' => $this->option('first'),
            'last_name' => $this->option('last'),
            'password' => $password, // haché par le cast "hashed" du modèle
            'role' => 'owner',
            'status' => 'active',
            'restaurant_id' => null,
            'is_platform_admin' => true,
        ])->save();

        $this->info("Super Admin prêt : {$user->first_name} {$user->last_name} ({$user->phone}).");

        return self::SUCCESS;
    }
}
