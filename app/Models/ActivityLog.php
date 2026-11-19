<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Journal des actions : qui a fait quoi, et quand. Le patron et la gérante
 * le consultent ; personne ne peut modifier ni effacer une ligne.
 */
class ActivityLog extends Model
{
    use BelongsToRestaurant;

    public const UPDATED_AT = null;

    /** Familles d'actions (filtre de l'écran). */
    public const GROUPS = [
        'sensitive' => 'Sensibles',
        'money' => 'Caisse et ventes',
        'stock' => 'Stock et prix',
        'team' => 'Équipe',
        'login' => 'Connexions',
    ];

    /**
     * action => [libellé, gravité (info | warning | danger), famille, icône]
     */
    public const ACTIONS = [
        'login' => ['Connexion', 'info', 'login', 'lock'],
        'order_cancelled' => ['Bon annulé', 'danger', 'money', 'x'],
        'item_removed' => ['Article retiré', 'warning', 'money', 'minus'],
        'item_offered' => ['Article offert', 'warning', 'money', 'gift'],
        'offer_cancelled' => ['Offert annulé', 'info', 'money', 'gift'],
        'discount' => ['Remise', 'warning', 'money', 'tag'],
        'credit_sale' => ['Vente à crédit', 'warning', 'money', 'notebook'],
        'credit_repayment' => ['Crédit remboursé', 'info', 'money', 'notebook'],
        'credit_limit' => ['Plafond de crédit', 'info', 'money', 'notebook'],
        'register_opened' => ['Caisse ouverte', 'info', 'money', 'cash'],
        'register_closed' => ['Caisse clôturée', 'info', 'money', 'cash'],
        'expense' => ['Dépense', 'info', 'money', 'wallet'],
        'price_changed' => ['Prix modifié', 'warning', 'stock', 'tag'],
        'product_created' => ['Produit ajouté', 'info', 'stock', 'box'],
        'stock_delivery' => ['Livraison', 'info', 'stock', 'truck'],
        'stock_loss' => ['Perte ou casse', 'warning', 'stock', 'alert'],
        'stock_count' => ['Comptage', 'info', 'stock', 'clipboard'],
        'empties_alert' => ['Vides en trop', 'danger', 'stock', 'bottle'],
        'user_created' => ['Compte créé', 'info', 'team', 'users'],
        'user_updated' => ['Compte modifié', 'info', 'team', 'users'],
        'password_reset' => ['Mot de passe provisoire', 'warning', 'team', 'lock'],
        'user_deactivated' => ['Compte désactivé', 'warning', 'team', 'users'],
        'user_reactivated' => ['Compte réactivé', 'info', 'team', 'users'],
        'user_removed' => ['Retiré du maquis', 'warning', 'team', 'users'],
        'password_changed' => ['Mot de passe changé', 'info', 'team', 'lock'],
        'settings_changed' => ['Réglages modifiés', 'info', 'team', 'sheet'],
    ];

    protected $fillable = ['restaurant_id', 'user_id', 'user_name', 'action', 'description', 'amount', 'meta', 'ip'];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Le journal des actions ne se modifie pas.'));
        static::deleting(fn () => throw new LogicException('Le journal des actions ne s\'efface pas.'));
    }

    /**
     * Écrit une ligne. Passe par une insertion directe : le journal s'écrit
     * même quand le maquis est en lecture seule (une connexion, par exemple).
     *
     * @param  array{user?: ?User, restaurant_id?: ?int, amount?: ?int, meta?: array}  $options
     */
    public static function record(string $action, string $description, array $options = []): void
    {
        $user = array_key_exists('user', $options) ? $options['user'] : Auth::user();
        $restaurantId = $options['restaurant_id'] ?? $user?->currentRestaurantId();

        if (! $restaurantId) {
            return; // Super Admin hors d'un maquis : rien à journaliser ici
        }

        DB::table('activity_logs')->insert([
            'restaurant_id' => $restaurantId,
            'user_id' => $user?->id,
            'user_name' => $user ? mb_substr(trim($user->first_name . ' ' . $user->last_name), 0, 120) : null,
            'action' => $action,
            'description' => mb_substr($description, 0, 500),
            'amount' => $options['amount'] ?? null,
            'meta' => isset($options['meta']) ? json_encode($options['meta'], JSON_UNESCAPED_UNICODE) : null,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }

    public static function fcfa(int $n): string
    {
        return number_format($n, 0, ',', ' ') . ' F';
    }

    /** Actions d'une famille ; « sensitive » = toutes celles qui ne sont pas de simple information. */
    public static function actionsInGroup(string $group): array
    {
        return array_keys(array_filter(self::ACTIONS, fn ($a) => $group === 'sensitive' ? $a[1] !== 'info' : $a[2] === $group));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::ACTIONS[$this->action][0] ?? $this->action;
    }

    public function severity(): string
    {
        $severity = self::ACTIONS[$this->action][1] ?? 'info';

        // Article retiré avant l'envoi en cuisine : simple correction de saisie
        if ($this->action === 'item_removed' && empty($this->meta['sent'])) {
            return 'info';
        }

        // Une clôture avec écart, un comptage avec manque : à regarder
        if (in_array($this->action, ['register_closed', 'stock_count'], true) && ($this->amount ?? 0) < 0) {
            return 'warning';
        }

        return $severity;
    }

    public function icon(): string
    {
        return self::ACTIONS[$this->action][3] ?? 'file';
    }

    public function who(): string
    {
        return $this->user ? trim($this->user->first_name . ' ' . $this->user->last_name) : ($this->user_name ?: 'Compte supprimé');
    }
}
