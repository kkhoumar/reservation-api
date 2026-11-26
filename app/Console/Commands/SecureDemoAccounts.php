<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Lancé à chaque démarrage du serveur en ligne.
 *
 * Les comptes du maquis de démo (Koffi, Mariam, Aya, Jean) ne gardent jamais
 * le mot de passe « password », connu de tous :
 * - DEMO_PASSWORD réglé dans Render → leurs mots de passe deviennent celui-là ;
 * - sinon → mot de passe aléatoire (plus personne ne peut entrer).
 * Leurs connexions en cours sont fermées.
 */
class SecureDemoAccounts extends Command
{
    protected $signature = 'app:secure-demo';

    protected $description = 'Retire le mot de passe « password » des comptes de démo sur le serveur en ligne';

    public function handle(): int
    {
        if ($this->laravel->environment('local', 'testing')) {
            $this->line('Ordinateur du développeur : rien à faire.');

            return self::SUCCESS;
        }

        $chosen = DemoSeeder::password();
        $phones = array_map(fn ($a) => User::normalizePhone($a[2]), array_values(DemoSeeder::ACCOUNTS));
        $changed = 0;

        foreach (User::withoutGlobalScopes()->whereIn('phone', $phones)->get() as $user) {
            if ($chosen !== null && Hash::check($chosen, $user->password)) {
                continue;
            }
            if ($chosen === null && ! Hash::check('password', $user->password)) {
                continue;
            }

            $user->forceFill([
                'password' => $chosen ?? Str::random(40),
                'remember_token' => null,
            ])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $changed++;
        }

        $this->info($changed === 0
            ? 'Comptes de démo : déjà protégés.'
            : "Comptes de démo protégés : {$changed}" . ($chosen ? ' (mot de passe DEMO_PASSWORD).' : ' (mot de passe aléatoire).'));

        return self::SUCCESS;
    }
}
