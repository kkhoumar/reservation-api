<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use BelongsToRestaurant, HasFactory;

    /** Statuts dans lesquels on peut encore ajouter ou retirer des articles. */
    public const EDITABLE_STATUSES = ['created', 'in_preparation', 'served'];

    protected $fillable = [
        'restaurant_id', 'table_id', 'customer_id', 'user_id', 'order_number',
        'order_type', 'status', 'subtotal', 'total',
        'discount_amount', 'discount_reason', 'discount_by',
    ];

    /**
     * Numéro de commande : dérivé de l'ID (CMD-00042), donc unique par
     * construction, même si deux serveuses créent une commande à la même
     * seconde. Un numéro temporaire unique est posé le temps de l'insertion.
     */
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'TMP-' . Str::uuid();
            }
        });

        static::created(function (Order $order) {
            if (str_starts_with($order->order_number, 'TMP-')) {
                $order->order_number = 'CMD-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
                $order->saveQuietly();
            }
        });
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function discountedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discount_by');
    }

    /**
     * Sous-total = lignes facturées (un article offert compte 0 F) ;
     * total = sous-total - remise (jamais négatif).
     */
    public function recalculateTotal(): void
    {
        $subtotal = (int) $this->items()->sum('total');
        $discount = min((int) $this->discount_amount, $subtotal);

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total' => $subtotal - $discount,
        ]);
    }

    /** Un article offert ou une remise : un bon à 0 F peut alors être validé. */
    public function hasCommercialGesture(): bool
    {
        return $this->discount_amount > 0 || $this->items()->where('is_offered', true)->exists();
    }

    /** Seuls le patron et la gérante (ou le Super Admin) offrent ou font une remise. */
    public static function canGiveGestures(User $user): bool
    {
        return $user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager'], true);
    }

    /**
     * Offre $quantity unités d'une ligne. Si on n'offre qu'une partie
     * (1 bière sur 5), la ligne est coupée en deux. Le stock ne bouge pas :
     * l'article a bien été consommé.
     *
     * @throws BusinessRuleException
     */
    public function offerItem(int $itemId, int $quantity, User $by, string $reason): OrderItem
    {
        $this->guardGesture($by, $reason);

        return DB::transaction(function () use ($itemId, $quantity, $by, $reason) {
            $order = $this->lockForGesture();
            $item = OrderItem::where('order_id', $order->id)->lockForUpdate()->findOrFail($itemId);

            if ($item->is_offered) {
                throw new BusinessRuleException('Cet article est déjà offert.');
            }

            if ($quantity < 1 || $quantity > $item->quantity) {
                throw new BusinessRuleException("Quantité invalide : entre 1 et {$item->quantity}.");
            }

            $offered = [
                'is_offered' => true,
                'offered_reason' => trim($reason),
                'offered_by' => $by->id,
                'total' => 0,
            ];

            if ($quantity === $item->quantity) {
                $item->update($offered);
                $offeredItem = $item;
            } else {
                $remaining = $item->quantity - $quantity;
                $item->update(['quantity' => $remaining, 'total' => $item->unit_price * $remaining]);

                $offeredItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $item->unit_price,
                    'unit_cost' => $item->unit_cost,
                    'note' => $item->note,
                ] + $offered);
            }

            $order->recalculateTotal();
            $this->refresh();

            return $offeredItem;
        });
    }

    /**
     * Annule un « offert » (erreur) : la ligne redevient payante.
     *
     * @throws BusinessRuleException
     */
    public function cancelOffer(int $itemId, User $by): void
    {
        if (! static::canGiveGestures($by)) {
            throw new BusinessRuleException('Seuls le patron et la gérante peuvent modifier un article offert.');
        }

        DB::transaction(function () use ($itemId) {
            $order = $this->lockForGesture();
            $item = OrderItem::where('order_id', $order->id)->where('is_offered', true)->lockForUpdate()->findOrFail($itemId);

            $item->update([
                'is_offered' => false,
                'offered_reason' => null,
                'offered_by' => null,
                'total' => $item->unit_price * $item->quantity,
            ]);

            $order->recalculateTotal();
            $this->refresh();
        });
    }

    /**
     * Remise en FCFA sur le bon (remplace une remise précédente ; 0 = la retirer).
     *
     * @throws BusinessRuleException
     */
    public function applyDiscount(int $amount, User $by, string $reason): void
    {
        if ($amount > 0) {
            $this->guardGesture($by, $reason);
        } elseif (! static::canGiveGestures($by)) {
            throw new BusinessRuleException('Seuls le patron et la gérante peuvent faire une remise.');
        }

        DB::transaction(function () use ($amount, $by, $reason) {
            $order = $this->lockForGesture();
            $subtotal = (int) $order->items()->sum('total');

            if ($amount < 0 || $amount > $subtotal) {
                throw new BusinessRuleException('La remise doit être comprise entre 0 et ' . number_format($subtotal, 0, ',', ' ') . ' FCFA.');
            }

            $order->update([
                'discount_amount' => $amount,
                'discount_reason' => $amount > 0 ? trim($reason) : null,
                'discount_by' => $amount > 0 ? $by->id : null,
            ]);
            $order->recalculateTotal();
            $this->refresh();
        });
    }

    /** @throws BusinessRuleException */
    protected function guardGesture(User $by, string $reason): void
    {
        if (! static::canGiveGestures($by)) {
            throw new BusinessRuleException('Seuls le patron et la gérante peuvent offrir ou faire une remise.');
        }

        if (mb_strlen(trim($reason)) < 3) {
            throw new BusinessRuleException('Indiquez le motif (par exemple : bon client, erreur cuisine).');
        }
    }

    /** @throws BusinessRuleException */
    protected function lockForGesture(): self
    {
        $order = static::whereKey($this->id)->lockForUpdate()->firstOrFail();

        if ($order->isPaid() || $order->status === 'cancelled') {
            throw new BusinessRuleException('Ce bon est déjà réglé ou annulé : il ne peut plus être modifié.');
        }

        return $order;
    }

    /** Statuts d'une commande pas encore réglée (la table ne peut pas être libérée). */
    public const OPEN_STATUSES = ['created', 'in_preparation', 'served', 'awaiting_payment'];

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);

        // Paiement avant service : les clients restent et recommandent,
        // c'est le comptoir qui libère la table quand ils partent
        if (! $this->restaurant?->paysBeforeService()) {
            $this->restaurantTable?->update(['status' => 'free']);
        }
    }

    /**
     * Annule une commande non payée et remet ses articles en stock,
     * en « tout ou rien ».
     *
     * @throws BusinessRuleException
     */
    public function cancel(): void
    {
        DB::transaction(function () {
            $order = static::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                throw new BusinessRuleException('Cette commande est déjà payée : elle ne peut plus être annulée.');
            }

            if ($order->status === 'cancelled') {
                throw new BusinessRuleException('Cette commande est déjà annulée.');
            }

            foreach ($order->items as $item) {
                Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
            $this->status = 'cancelled';
        });
    }
}