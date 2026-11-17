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
 * sont automatiquement limitées à SON restaurant. Un ID appartenant à un
 * autre maquis devient donc introuvable (404), même s'il est deviné ou
 * modifié dans l'URL ou dans une requête Livewire.
 *
 * - Sans utilisateur connecté (console, files d'attente, seeders), aucun
 *   filtre n'est appliqué.
 * - Un utilisateur sans restaurant (ex. Super Admin) ne voit aucune donnée
 *   de maquis. Pour un accès volontaire à tous les maquis :
 *   Model::withoutGlobalScope('restaurant').
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

            if ($user->restaurant_id === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where($query->getModel()->qualifyColumn('restaurant_id'), $user->restaurant_id);
        });

        static::creating(function (Model $model) {
            if ($model->restaurant_id === null && ($user = Auth::user())) {
                $model->restaurant_id = $user->restaurant_id;
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
