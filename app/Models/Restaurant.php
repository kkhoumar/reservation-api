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
        'service_mode', 'credit_enabled', 'payment_timing',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'credit_enabled' => 'boolean',
    ];

    protected $attributes = [
        'service_mode' => 'both',
        'credit_enabled' => false,
        'payment_timing' => 'before',
    ];

    public const PAYMENT_TIMINGS = [
        'before' => ['label' => 'Paiement avant service', 'hint' => 'La serveuse apporte l\'argent au comptoir, puis on sert. Pas d\'argent, pas de boisson.'],
        'after' => ['label' => 'Paiement à la fin', 'hint' => 'Le client consomme, puis paie l\'addition en partant.'],
    ];

    /**
     * Le maquis a-t-il des serveuses actives ? Sinon, la personne qui prend
     * la commande encaisse elle-même : pas de comptoir, pas de sacoche.
     */
    public function hasActiveServers(): bool
    {
        return $this->users()->where('role', 'server')->where('status', 'active')->exists();
    }

    public function paysBeforeService(): bool
    {
        return $this->payment_timing !== 'after';
    }

    public const SERVICE_MODES = [
        'both' => ['label' => 'Tables et vente rapide', 'hint' => 'Service à table, et vente directe au comptoir.'],
        'tables' => ['label' => 'Service à table', 'hint' => 'Chaque commande est liée à une table.'],
        'quick' => ['label' => 'Vente rapide', 'hint' => 'Pas de tables : on sert et on encaisse directement.'],
    ];

    public function usesTables(): bool
    {
        return in_array($this->service_mode, ['tables', 'both'], true);
    }

    public function usesQuickSale(): bool
    {
        return in_array($this->service_mode, ['quick', 'both'], true);
    }

    public function creditEnabled(): bool
    {
        return $this->credit_enabled === true;
    }

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