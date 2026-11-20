<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use App\Models\Concerns\BelongsToRestaurantThroughOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Règlement d'une commande : espèces, Mobile Money, ou les deux (mixte).
 *
 * - payment_method : 'cash' si 100 % espèces, sinon l'opérateur Mobile Money ;
 * - mobile_amount  : part payée par Mobile Money ;
 * - amount_received / change_given : espèces reçues pour le reste, monnaie rendue.
 *
 * Seule la part espèces (amount_received - change_given) entre dans la caisse
 * ou la sacoche ; le Mobile Money arrive sur le téléphone du maquis.
 */
class Payment extends Model
{
    use BelongsToRestaurantThroughOrder, HasFactory;

    public const METHODS = [
        'cash' => 'Espèces',
        'wave' => 'Wave',
        'orange_money' => 'Orange Money',
        'mtn_momo' => 'MTN MoMo',
        'moov_money' => 'Moov Money',
    ];

    public const MOBILE_METHODS = ['wave', 'orange_money', 'mtn_momo', 'moov_money'];

    protected $fillable = [
        'order_id', 'user_id', 'payment_method',
        'amount_due', 'amount_received', 'change_given', 'mobile_amount',
        'reference', 'status', 'in_sacoche', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'in_sacoche' => 'boolean',
    ];

    protected $attributes = [
        'mobile_amount' => 0,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Espèces réellement gardées (entrées en caisse ou en sacoche). */
    public function cashPart(): int
    {
        return (int) $this->amount_received - (int) $this->change_given;
    }

    public function isMobile(): bool
    {
        return $this->mobile_amount > 0;
    }

    public function isMixed(): bool
    {
        return $this->isMobile() && $this->cashPart() > 0;
    }

    /** « Espèces », « Wave », « Wave + espèces ». */
    public function methodLabel(): string
    {
        if (! $this->isMobile()) {
            return self::METHODS['cash'];
        }

        $label = self::METHODS[$this->payment_method] ?? $this->payment_method;

        return $this->isMixed() ? $label . ' + espèces' : $label;
    }

    /**
     * Encaissement 100 % espèces.
     *
     * @throws BusinessRuleException
     */
    public static function recordCashPayment(Order $order, User $cashier, int $amountReceived): self
    {
        return static::recordPayment($order, $cashier, $amountReceived);
    }

    /**
     * Encaissement en « tout ou rien » : paiement + commande payée (+ table
     * libérée en paiement à la fin), ou rien du tout.
     *
     * La commande est verrouillée pendant l'opération : deux clics
     * simultanés (ou deux caissiers) ne peuvent pas la payer deux fois.
     *
     * @param  int  $cashReceived  espèces reçues (pour la part non payée en Mobile Money)
     * @param  string|null  $mobileMethod  wave | orange_money | mtn_momo | moov_money
     * @param  int  $mobileAmount  part payée par Mobile Money
     *
     * @throws BusinessRuleException
     */
    public static function recordPayment(
        Order $order,
        User $cashier,
        int $cashReceived,
        ?string $mobileMethod = null,
        int $mobileAmount = 0,
        ?string $reference = null,
    ): self {
        return DB::transaction(function () use ($order, $cashier, $cashReceived, $mobileMethod, $mobileAmount, $reference) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                throw new BusinessRuleException('Cette commande est déjà payée.');
            }

            if ($order->status === 'cancelled') {
                throw new BusinessRuleException('Cette commande a été annulée.');
            }

            // Un bon à 0 F n'est accepté que s'il est entièrement offert ou remisé
            if ($order->total <= 0 && ! $order->hasCommercialGesture()) {
                throw new BusinessRuleException('Impossible d\'encaisser une commande vide.');
            }

            if ($cashReceived < 0 || $mobileAmount < 0) {
                throw new BusinessRuleException('Les montants ne peuvent pas être négatifs.');
            }

            if ($mobileAmount > 0 && ! in_array($mobileMethod, self::MOBILE_METHODS, true)) {
                throw new BusinessRuleException('Choisissez l\'opérateur Mobile Money.');
            }

            if ($mobileAmount > $order->total) {
                throw new BusinessRuleException('Le montant Mobile Money dépasse le total de la commande.');
            }

            $cashDue = $order->total - $mobileAmount;

            if ($cashReceived < $cashDue) {
                throw new BusinessRuleException($mobileAmount > 0
                    ? 'Il manque ' . number_format($cashDue - $cashReceived, 0, ',', ' ') . ' FCFA en espèces pour compléter le paiement.'
                    : 'Le montant reçu est inférieur au total de la commande.');
            }

            if (static::where('order_id', $order->id)->where('status', 'completed')->exists()) {
                throw new BusinessRuleException('Un paiement existe déjà pour cette commande.');
            }

            // L'argent encaissé doit toujours tomber dans une caisse ouverte :
            // sinon il n'est compté nulle part et peut disparaître sans écart.
            if (! CashRegister::isOpenFor($order->restaurant_id)) {
                throw new BusinessRuleException('La caisse est fermée. Ouvrez la caisse pour encaisser.');
            }

            $payment = static::create([
                'order_id' => $order->id,
                'user_id' => $cashier->id,
                'payment_method' => $mobileAmount > 0 ? $mobileMethod : 'cash',
                'amount_due' => $order->total,
                'amount_received' => $cashReceived,
                'change_given' => $cashReceived - $cashDue,
                'mobile_amount' => $mobileAmount,
                'reference' => $mobileAmount > 0 && $reference ? mb_substr(trim($reference), 0, 100) : null,
                'status' => 'completed',
                // Encaissé par une serveuse : la part espèces reste dans sa
                // sacoche jusqu'au versement à la caisse
                'in_sacoche' => $cashier->isServer(),
                'paid_at' => now(),
            ]);

            $order->markAsPaid();

            return $payment;
        });
    }
}
