<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Séance d'inventaire : on compte le frigo et l'on compare avec l'application.
 */
class StockCount extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'user_id', 'products_counted', 'quantity_gap', 'value_gap', 'note',
    ];

    protected $casts = [
        'products_counted' => 'integer',
        'quantity_gap' => 'integer',
        'value_gap' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
