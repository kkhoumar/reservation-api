<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurantThroughOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use BelongsToRestaurantThroughOrder, HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'quantity', 'unit_price', 'unit_cost', 'total', 'note',
        'is_offered', 'offered_reason', 'offered_by',
    ];

    protected $casts = [
        'is_offered' => 'boolean',
    ];

    protected $attributes = [
        'is_offered' => false,
    ];

    public function offeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offered_by');
    }

    /** Valeur au prix normal (ce que l'article aurait coûté). */
    public function fullValue(): int
    {
        return (int) $this->unit_price * (int) $this->quantity;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}