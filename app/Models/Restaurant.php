<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'logo', 'type', 'phone', 'email', 'address',
        'commune', 'city', 'opening_hours',
        'subscription_status', 'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    public function isSuspended(): bool
    {
        return $this->subscription_status === 'suspended';
    }

    public function isTrialExpired(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    /**
     * Maquis verrouillé : suspendu par le Super Admin (non-paiement)
     * ou période d'essai terminée. Son personnel ne peut plus utiliser
     * l'application ; les données sont conservées.
     */
    public function isLocked(): bool
    {
        return $this->isSuspended() || $this->isTrialExpired();
    }
}