<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'payment_method',
        'amount_due', 'amount_received', 'change_given',
        'reference', 'status', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function recordCashPayment(Order $order, User $cashier, int $amountReceived): self
    {
        $payment = static::create([
            'order_id' => $order->id,
            'user_id' => $cashier->id,
            'payment_method' => 'cash',
            'amount_due' => $order->total,
            'amount_received' => $amountReceived,
            'change_given' => max(0, $amountReceived - $order->total),
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $order->markAsPaid();

        return $payment;
    }
}