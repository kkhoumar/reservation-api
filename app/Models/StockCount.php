<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Comptage du stock : le patron compte ce qui est vraiment là et l'application
 * compare avec ses calculs depuis le comptage précédent.
 */
class StockCount extends Model
{
    use BelongsToRestaurant;

    /** Au-delà, l'application rappelle de compter. */
    public const REMIND_AFTER_DAYS = 7;

    protected $fillable = [
        'restaurant_id', 'user_id', 'products_counted', 'quantity_gap', 'value_gap',
        'gap_sale_value', 'cost_value', 'sale_value', 'note', 'previous_count_at', 'unbilled_value',
    ];

    protected $casts = [
        'products_counted' => 'integer',
        'quantity_gap' => 'integer',
        'value_gap' => 'integer',
        'gap_sale_value' => 'integer',
        'cost_value' => 'integer',
        'sale_value' => 'integer',
        'unbilled_value' => 'integer',
        'previous_count_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }
}
