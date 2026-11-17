<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id', 'table_id', 'user_id', 'order_number',
        'order_type', 'status', 'subtotal', 'total',
    ];

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

    public static function generateOrderNumber(): string
    {
        return 'CMD-' . str_pad((string) (static::max('id') + 1), 5, '0', STR_PAD_LEFT);
    }
}