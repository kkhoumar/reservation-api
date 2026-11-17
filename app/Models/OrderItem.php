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
        'order_id', 'product_id', 'quantity', 'unit_price', 'total', 'note',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}