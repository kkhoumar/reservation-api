<?php

namespace App\Support;

use App\Exceptions\BusinessRuleException;
use Illuminate\Support\Facades\Auth;

/**
 * Mode « lecture seule » d'un maquis verrouillé (impayé, suspendu, essai fini) :
 * le patron et la gérante consultent leurs chiffres, mais rien ne peut être
 * enregistré. Appelé avant chaque écriture sur les données d'un maquis
 * (voir BelongsToRestaurant) : c'est le filet de sécurité central.
 *
 * Sans utilisateur connecté (tâches, commandes, seeders) ou pour le
 * Super Admin, aucune restriction.
 */
class ReadOnlyGuard
{
    public const MESSAGE = 'Abonnement suspendu : le maquis est en lecture seule. Réglez l\'abonnement pour pouvoir de nouveau enregistrer.';

    public static function isReadOnly(): bool
    {
        $user = Auth::user();

        if (! $user || $user->isPlatformAdmin()) {
            return false;
        }

        return (bool) $user->restaurant?->isLocked();
    }

    /** @throws BusinessRuleException */
    public static function assertWritable(): void
    {
        if (self::isReadOnly()) {
            throw new BusinessRuleException(self::MESSAGE);
        }
    }
}
