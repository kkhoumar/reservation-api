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

    public const ASSOCIATE_MESSAGE = 'Compte associé : vous consultez les chiffres, mais vous ne pouvez rien enregistrer ni modifier.';

    public static function isReadOnly(): bool
    {
        $user = Auth::user();

        if (! $user || $user->isPlatformAdmin()) {
            return false;
        }

        // L'associé regarde tout, sans jamais rien modifier
        if ($user->isAssociate()) {
            return true;
        }

        return (bool) $user->restaurant?->isLocked();
    }

    /** Maquis verrouillé pour impayé (et non simple compte associé). */
    public static function isLocked(): bool
    {
        $user = Auth::user();

        return $user && ! $user->isPlatformAdmin() && (bool) $user->restaurant?->isLocked();
    }

    public static function message(): string
    {
        return Auth::user()?->isAssociate() && ! self::isLocked() ? self::ASSOCIATE_MESSAGE : self::MESSAGE;
    }

    /** @throws BusinessRuleException */
    public static function assertWritable(): void
    {
        if (self::isReadOnly()) {
            throw new BusinessRuleException(self::message());
        }
    }
}
