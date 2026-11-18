<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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
        'must_change_password' => 'boolean',
    ];

    /**
     * Met un numéro ivoirien au format unique « 0711223344 » :
     * espaces, points, tirets et indicatif +225 / 00225 retirés.
     */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 15 && str_starts_with($digits, '00225')) {
            $digits = substr($digits, 5);
        } elseif (strlen($digits) === 13 && str_starts_with($digits, '225')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    /** Numéro ivoirien normalisé : 10 chiffres, mobile (01, 05, 07) ou fixe (21, 25, 27). */
    public const PHONE_REGEX = '/^[02]\d{9}$/';

    protected function phone(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (?string $value) => self::normalizePhone($value),
        );
    }

    public function mustChangePassword(): bool
    {
        return $this->must_change_password === true;
    }

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

    /**
     * Argent liquide encaissé par la serveuse (monnaie déjà rendue).
     * La part Mobile Money n'est pas dans sa sacoche.
     */
    public function sacocheCollected(): int
    {
        return (int) Payment::where('user_id', $this->id)
            ->where('in_sacoche', true)
            ->where('status', 'completed')
            ->sum(DB::raw('amount_received - change_given'));
    }

    public function sacocheRemitted(): int
    {
        return (int) CashRemittance::withoutGlobalScopes()
            ->where('server_id', $this->id)
            ->sum('amount');
    }

    /**
     * Ce que la serveuse a encore sur elle et doit verser à la caisse.
     */
    public function sacocheBalance(): int
    {
        return $this->sacocheCollected() - $this->sacocheRemitted();
    }

    public function canManagePricing(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }
}