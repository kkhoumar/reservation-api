<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formule d'abonnement à la plateforme (Basic, Pro, Premium).
 * Données de la plateforme : pas liées à un maquis.
 */
class Plan extends Model
{
    protected $fillable = [
        'code', 'name', 'monthly_price', 'max_users', 'description', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'monthly_price' => 'integer',
        'max_users' => 'integer',
        'is_active' => 'boolean',
    ];

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function usersLabel(): string
    {
        return $this->max_users === null ? 'Personnel illimité' : "Jusqu'à {$this->max_users} utilisateurs";
    }
}
