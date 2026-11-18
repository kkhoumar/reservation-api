<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use BelongsToRestaurant, HasFactory;

    protected $table = 'restaurant_tables';

    protected $fillable = ['restaurant_id', 'zone_id', 'label', 'capacity', 'status'];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'free' => '#1E8E5A',
            'occupied' => '#D2622A',
            'awaiting_payment' => '#D93B3B',
            default => '#5B6B65',
        };
    }
}