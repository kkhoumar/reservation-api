<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use App\Models\Concerns\BelongsToRestaurantThroughOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    use BelongsToRestaurantThroughOrder, HasFactory;

    protected $fillable = [
        'order_id', 'user_id', 'payment_method',
        'amount_due', 'amount_received', 'change_given',
        'reference', 'status', 'in_sacoche', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'in_sacoche' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Encaissement en espèces, en « tout ou rien » :
     * paiement + commande payée + table libérée, ou rien du tout.
     *
     * La commande est verrouillée pendant l'opération : deux clics
     * simultanés (ou deux caissiers) ne peuvent pas la payer deux fois.
     *
     * @throws BusinessRuleException
     */
    public static function recordCashPayment(Order $order, User $cashier, int $amountReceived): self
    {
        return DB::transaction(function () use ($order, $cashier, $amountReceived) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                throw new BusinessRuleException('Cette commande est déjà payée.');
            }

            if ($order->status === 'cancelled') {
                throw new BusinessRuleException('Cette commande a été annulée.');
            }

            if ($order->total <= 0) {
                throw new BusinessRuleException('Impossible d\'encaisser une commande vide.');
            }

            if ($amountReceived < $order->total) {
                throw new BusinessRuleException('Le montant reçu est inférieur au total de la commande.');
            }

            if (static::where('order_id', $order->id)->where('status', 'completed')->exists()) {
                throw new BusinessRuleException('Un paiement existe déjà pour cette commande.');
            }

            $payment = static::create([
                'order_id' => $order->id,
                'user_id' => $cashier->id,
                'payment_method' => 'cash',
                'amount_due' => $order->total,
                'amount_received' => $amountReceived,
                'change_given' => $amountReceived - $order->total,
                'status' => 'completed',
                // Encaissé par une serveuse : l'argent reste dans sa sacoche
                // jusqu'au versement à la caisse
                'in_sacoche' => $cashier->isServer(),
                'paid_at' => now(),
            ]);

            $order->markAsPaid();

            return $payment;
        });
    }
}