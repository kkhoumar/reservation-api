<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use BelongsToRestaurant, HasFactory;

    /** Statuts dans lesquels on peut encore ajouter ou retirer des articles. */
    public const EDITABLE_STATUSES = ['created', 'in_preparation', 'served'];

    protected $fillable = [
        'restaurant_id', 'table_id', 'customer_id', 'user_id', 'order_number',
        'order_type', 'status', 'subtotal', 'total',
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

    public function recalculateTotal(): void
    {
        $subtotal = $this->items()->sum('total');
        $this->update(['subtotal' => $subtotal, 'total' => $subtotal]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
        $this->restaurantTable?->update(['status' => 'free']);
    }
}