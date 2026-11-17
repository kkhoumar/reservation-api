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
        'password', 'role', 'status', 'is_platform_admin',
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

    public function canManagePricing(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }
}