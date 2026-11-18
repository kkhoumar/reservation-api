<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToRestaurant, HasFactory;

    protected $fillable = ['restaurant_id', 'name', 'phone', 'credit_limit', 'note'];

    protected $casts = [
        'credit_limit' => 'integer',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Encours = total des ventes à crédit - total des remboursements.
     */
    public function balance(): int
    {
        $charges = (int) $this->transactions()->where('type', 'charge')->sum('amount');
        $repayments = (int) $this->transactions()->where('type', 'repayment')->sum('amount');

        return $charges - $repayments;
    }

    /**
     * Message de relance WhatsApp, poli et prêt à envoyer.
     */
    public function reminderMessage(string $restaurantName): string
    {
        return "Bonjour {$this->name}, ici {$restaurantName}. Votre crédit chez nous est de "
            . number_format($this->balance(), 0, ',', ' ')
            . " FCFA. Merci de passer la régler quand vous pourrez. Bonne journée !";
    }

    public function whatsappUrl(string $restaurantName): ?string
    {
        if (! $this->phone) {
            return null;
        }

        return 'https://wa.me/225' . User::normalizePhone($this->phone)
            . '?text=' . rawurlencode($this->reminderMessage($restaurantName));
    }
}