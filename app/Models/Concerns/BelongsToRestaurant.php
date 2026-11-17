<?php

namespace App\Models\Concerns;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Isolation multi-restaurant (multi-tenant).
 *
 * Dès qu'un utilisateur est connecté, toutes les requêtes sur le modèle
 * sont automatiquement limitées au maquis courant (User::currentRestaurantId()).
 * Un ID appartenant à un autre maquis devient donc introuvable (404), même
 * s'il est deviné ou modifié dans l'URL ou dans une requête Livewire.
 *
 * - Patron / employé : uniquement son maquis. Sans maquis, il ne voit rien.
 * - Super Admin : le maquis dans lequel il est « entré ». S'il n'est entré
 *   dans aucun maquis, il voit TOUTES les données (propriétaire de la plateforme).
 * - Sans utilisateur connecté (console, files d'attente, seeders) : aucun filtre.
 * - À la création, restaurant_id est rempli automatiquement s'il est vide.
 */
trait BelongsToRestaurant
{
    public static function bootBelongsToRestaurant(): void
    {
        static::addGlobalScope('restaurant', function (Builder $query) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $restaurantId = $user->currentRestaurantId();

            if ($restaurantId === null) {
                if (! $user->isPlatformAdmin()) {
                    $query->whereRaw('1 = 0');
                }

                return;
            }

            $query->where($query->getModel()->qualifyColumn('restaurant_id'), $restaurantId);
        });

        static::creating(function (Model $model) {
            if ($model->restaurant_id === null && ($user = Auth::user())) {
                $model->restaurant_id = $user->currentRestaurantId();
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
