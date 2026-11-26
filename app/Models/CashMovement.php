<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Argent ajouté au tiroir ou retiré du tiroir pendant la caisse.
 * Ni une vente, ni une dépense : le bénéfice ne change pas.
 */
class CashMovement extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'cash_register_id', 'user_id', 'type', 'amount', 'reason', 'note'];

    /** Raisons toutes prêtes (on touche, on n'écrit pas). */
    public const REASONS = [
        'in' => [
            'forgotten' => "Monnaie oubliée à l'ouverture",
            'owner_brought' => 'Le patron a apporté de la monnaie',
            'other' => 'Autre',
        ],
        'out' => [
            'owner_took' => "Le patron récupère l'argent",
            'safekeeping' => 'Mis en sécurité',
            'other' => 'Autre',
        ],
    ];

    /** Ajouter : patron, gérante, caissier. */
    public static function canAdd(User $user): bool
    {
        return CashRegister::canOpen($user);
    }

    /** Retirer : patron et gérante seulement (sinon un retrait pourrait cacher un vol). */
    public static function canRemove(User $user): bool
    {
        return $user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager'], true);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->type][$this->reason] ?? $this->reason;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }
}
