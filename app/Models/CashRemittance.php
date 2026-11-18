<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Versement : une serveuse vide (tout ou partie de) sa sacoche à la caisse.
 * L'argent sort de sa sacoche et entre dans la caisse ouverte.
 */
class CashRemittance extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id', 'server_id', 'received_by', 'cash_register_id', 'amount', 'note',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'server_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Enregistre un versement, en « tout ou rien ».
     *
     * La serveuse est verrouillée pendant l'opération : deux clics simultanés
     * ne peuvent pas verser deux fois le même argent.
     *
     * @throws BusinessRuleException
     */
    public static function record(User $server, User $receiver, int $amount, ?string $note = null): self
    {
        return DB::transaction(function () use ($server, $receiver, $amount, $note) {
            $server = User::whereKey($server->id)->lockForUpdate()->firstOrFail();
            $restaurantId = $receiver->currentRestaurantId();

            if (! $server->isServer() || $server->restaurant_id !== $restaurantId) {
                throw new BusinessRuleException('Cette personne n\'est pas une serveuse de ce maquis.');
            }

            if ($amount <= 0) {
                throw new BusinessRuleException('Le montant du versement doit être supérieur à zéro.');
            }

            $balance = $server->sacocheBalance();

            if ($amount > $balance) {
                throw new BusinessRuleException(
                    'Montant trop élevé : la sacoche de ' . $server->first_name . ' contient '
                    . number_format($balance, 0, ',', ' ') . ' FCFA.'
                );
            }

            $register = CashRegister::where('restaurant_id', $restaurantId)
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->latest('opened_at')
                ->first();

            if (! $register) {
                throw new BusinessRuleException('Ouvrez d\'abord la caisse pour recevoir un versement.');
            }

            return static::create([
                'restaurant_id' => $restaurantId,
                'server_id' => $server->id,
                'received_by' => $receiver->id,
                'cash_register_id' => $register->id,
                'amount' => $amount,
                'note' => $note ?: null,
            ]);
        });
    }
}
