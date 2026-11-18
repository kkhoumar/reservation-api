<?php

namespace App\Policies;

use App\Models\User;

/**
 * Qui peut gérer quel compte du personnel.
 *
 * - Super Admin : tout.
 * - Propriétaire : gérants, caissiers, serveurs, et son propre profil.
 * - Gérant(e)    : caissiers et serveurs uniquement.
 * - Les autres   : rien.
 */
class UserPolicy
{
    /** Rôles qu'un utilisateur a le droit d'attribuer. */
    public static function assignableRoles(User $actor): array
    {
        if ($actor->isPlatformAdmin() || $actor->role === 'owner') {
            return ['manager', 'cashier', 'server'];
        }

        if ($actor->role === 'manager') {
            return ['cashier', 'server'];
        }

        return [];
    }

    public function viewAny(User $actor): bool
    {
        return $actor->isPlatformAdmin() || in_array($actor->role, ['owner', 'manager'], true);
    }

    public function create(User $actor): bool
    {
        return self::assignableRoles($actor) !== [];
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->isPlatformAdmin()) {
            return true;
        }

        if ($actor->restaurant_id === null || $actor->restaurant_id !== $target->restaurant_id) {
            return false;
        }

        // Le propriétaire peut modifier son propre profil
        if ($actor->id === $target->id) {
            return $actor->role === 'owner';
        }

        return in_array($target->role, self::assignableRoles($actor), true);
    }

    public function toggleStatus(User $actor, User $target): bool
    {
        // Personne ne se désactive soi-même
        return $actor->id !== $target->id && $this->update($actor, $target);
    }

    /**
     * Retirer quelqu'un du maquis (départ). Jamais soi-même, jamais le
     * propriétaire ; une gérante ne retire que des caissiers et des serveurs.
     */
    public function remove(User $actor, User $target): bool
    {
        return $target->role !== 'owner' && $this->toggleStatus($actor, $target);
    }
}
