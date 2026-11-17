<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
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
            'free' => '#65B96E',
            'occupied' => '#E84A3A',
            'awaiting_payment' => '#FF7A00',
            default => '#6B7280',
        };
    }
}