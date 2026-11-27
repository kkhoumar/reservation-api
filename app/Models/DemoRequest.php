<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un prospect qui demande une démo depuis le site.
 * Le Super Admin le rappelle, puis crée son maquis.
 *
 * Pas de BelongsToRestaurant : ces demandes appartiennent à la plateforme.
 */
class DemoRequest extends Model
{
    use HasFactory;

    public const STATUSES = [
        'new' => 'Nouvelle',
        'contacted' => 'Contacté',
        'converted' => 'Client créé',
        'rejected' => 'Sans suite',
    ];

    protected $fillable = [
        'contact_name', 'contact_first_name', 'contact_last_name', 'restaurant_name', 'phone', 'commune', 'message',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['new', 'contacted'], true);
    }
}
