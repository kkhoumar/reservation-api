<?php

namespace App\Models\Concerns;

use App\Support\ReadOnlyGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Isolation pour les modèles qui n'ont pas de restaurant_id mais
 * appartiennent à une commande (lignes de commande, paiements).
 *
 * On ne garde que les lignes dont la commande est visible : la commande
 * étant elle-même filtrée par BelongsToRestaurant, l'isolation suit.
 */
trait BelongsToRestaurantThroughOrder
{
    public static function bootBelongsToRestaurantThroughOrder(): void
    {
        static::addGlobalScope('restaurant', function (Builder $query) {
            if (Auth::user()) {
                $query->whereHas('order');
            }
        });

        // Maquis verrouillé : lecture seule (paiements, lignes de commande)
        static::saving(fn () => ReadOnlyGuard::assertWritable());
        static::updating(fn () => ReadOnlyGuard::assertWritable()); // increment(), decrement()
        static::deleting(fn () => ReadOnlyGuard::assertWritable());
    }
}
