<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Mouvement d'ardoise :
 * - charge    : un bon mis sur l'ardoise (le client doit cet argent) ;
 * - repayment : le client rembourse en espèces (l'argent entre en caisse).
 */
class CreditTransaction extends Model
{
    use BelongsToRestaurant, HasFactory;

    protected $fillable = [
        'restaurant_id', 'customer_id', 'order_id', 'user_id', 'cash_register_id',
        'type', 'amount', 'note',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Seuls le patron et la gérante (ou le Super Admin) autorisent un crédit. */
    public static function canAuthorizeCredit(User $user): bool
    {
        return $user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager'], true);
    }

    /**
     * Met un bon sur l'ardoise d'un client, en « tout ou rien » :
     * mouvement d'ardoise + bon réglé (sans argent en caisse).
     *
     * @throws BusinessRuleException
     */
    public static function chargeOrder(Order $order, Customer $customer, User $by): self
    {
        if (! static::canAuthorizeCredit($by)) {
            throw new BusinessRuleException('Seuls le patron et la gérante peuvent mettre un bon sur l\'ardoise.');
        }

        return DB::transaction(function () use ($order, $customer, $by) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            if (! $order->restaurant?->creditEnabled()) {
                throw new BusinessRuleException('L\'ardoise n\'est pas activée pour ce maquis.');
            }

            if ($customer->restaurant_id !== $order->restaurant_id) {
                throw new BusinessRuleException('Ce client n\'est pas un client de ce maquis.');
            }

            if ($order->status !== 'awaiting_payment') {
                throw new BusinessRuleException('Ce bon a déjà été réglé ou annulé.');
            }

            if ($order->total <= 0) {
                throw new BusinessRuleException('Impossible de mettre un bon vide sur l\'ardoise.');
            }

            $balance = $customer->balance();

            if ($customer->credit_limit !== null && $balance + $order->total > $customer->credit_limit) {
                throw new BusinessRuleException(
                    "Plafond dépassé : {$customer->name} doit déjà " . number_format($balance, 0, ',', ' ')
                    . ' FCFA (plafond ' . number_format($customer->credit_limit, 0, ',', ' ') . ' FCFA).'
                );
            }

            $transaction = static::create([
                'restaurant_id' => $order->restaurant_id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'user_id' => $by->id,
                'type' => 'charge',
                'amount' => $order->total,
            ]);

            $order->update(['customer_id' => $customer->id]);
            $order->markAsPaid();

            return $transaction;
        });
    }

    /**
     * Le client rembourse en espèces : sa dette baisse, l'argent entre
     * dans la caisse ouverte.
     *
     * @throws BusinessRuleException
     */
    public static function recordRepayment(Customer $customer, User $by, int $amount, ?string $note = null): self
    {
        return DB::transaction(function () use ($customer, $by, $amount, $note) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            if ($amount <= 0) {
                throw new BusinessRuleException('Le montant du remboursement doit être supérieur à zéro.');
            }

            $balance = $customer->balance();

            if ($amount > $balance) {
                throw new BusinessRuleException(
                    "Montant trop élevé : {$customer->name} ne doit que " . number_format($balance, 0, ',', ' ') . ' FCFA.'
                );
            }

            $register = CashRegister::where('restaurant_id', $customer->restaurant_id)
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->latest('opened_at')
                ->first();

            if (! $register) {
                throw new BusinessRuleException('Ouvrez d\'abord la caisse pour encaisser un remboursement.');
            }

            return static::create([
                'restaurant_id' => $customer->restaurant_id,
                'customer_id' => $customer->id,
                'user_id' => $by->id,
                'cash_register_id' => $register->id,
                'type' => 'repayment',
                'amount' => $amount,
                'note' => $note ?: null,
            ]);
        });
    }
}
