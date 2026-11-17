<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'restaurant_id', 'first_name', 'last_name', 'phone', 'email',
        'password', 'role', 'status',
        // is_platform_admin volontairement absent : jamais assignable en masse
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_platform_admin' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isServer(): bool
    {
        return $this->role === 'server';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin === true;
    }

    /**
     * Clé de session du maquis dans lequel le Super Admin est « entré ».
     */
    public const ADMIN_RESTAURANT_SESSION_KEY = 'admin_restaurant_id';

    /**
     * Le maquis sur lequel l'utilisateur travaille en ce moment :
     * - patron / employé : son propre maquis ;
     * - Super Admin : le maquis qu'il a choisi d'ouvrir (null = aucun).
     */
    public function currentRestaurantId(): ?int
    {
        if ($this->isPlatformAdmin()) {
            $id = session(self::ADMIN_RESTAURANT_SESSION_KEY);

            return $id !== null ? (int) $id : null;
        }

        return $this->restaurant_id;
    }

    public function currentRestaurant(): ?Restaurant
    {
        $id = $this->currentRestaurantId();

        return $id ? Restaurant::find($id) : null;
    }

    public function canManagePricing(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }
}