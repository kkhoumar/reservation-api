<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rappel d'échéance envoyé (ou à envoyer à la main) au patron d'un maquis.
 * Données de la plateforme : pas de filtre automatique par maquis.
 */
class SubscriptionReminder extends Model
{
    protected $fillable = ['restaurant_id', 'kind', 'due_date', 'status', 'phone', 'error'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
