<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mouvement de stock hors vente. Les ventes, elles, sont tracées par les
 * lignes de commande (order_items).
 */
class StockMovement extends Model
{
    use BelongsToRestaurant;

    public const TYPES = [
        'initial' => 'Stock de départ',
        'delivery' => 'Livraison',
        'loss' => 'Perte ou casse',
        'inventory' => 'Comptage',
    ];

    protected $fillable = [
        'restaurant_id', 'product_id', 'user_id', 'type', 'quantity',
        'stock_before', 'stock_after', 'unit_cost', 'supplier', 'reason',
        'stock_count_id', 'expense_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'unit_cost' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
