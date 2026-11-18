<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement d'un maquis à la plateforme (enregistré par le Super Admin).
 *
 * Pas de filtre automatique par maquis : la liste complète est réservée au
 * Super Admin, et le patron ne voit que ceux de son maquis (filtre explicite).
 */
class SubscriptionPayment extends Model
{
    public const METHODS = [
        'wave' => 'Wave',
        'orange_money' => 'Orange Money',
        'mtn_momo' => 'MTN MoMo',
        'moov_money' => 'Moov Money',
        'cash' => 'Espèces',
        'bank' => 'Virement / chèque',
    ];

    protected $fillable = [
        'restaurant_id', 'plan_id', 'amount', 'months', 'method', 'reference',
        'period_start', 'period_end', 'recorded_by', 'note',
    ];

    protected $casts = [
        'amount' => 'integer',
        'months' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
