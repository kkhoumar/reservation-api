<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToRestaurant, HasFactory;

    protected $fillable = ['restaurant_id', 'name', 'phone', 'note'];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Encours = total des ventes à crédit - total des remboursements.
     */
    public function balance(): int
    {
        $charges = $this->transactions()->where('type', 'charge')->sum('amount');
        $repayments = $this->transactions()->where('type', 'repayment')->sum('amount');

        return $charges - $repayments;
    }
}