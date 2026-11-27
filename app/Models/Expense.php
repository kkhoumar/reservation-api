<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToRestaurant, HasFactory;

    public const CATEGORIES = [
        'goods_purchase' => 'Achat de marchandises',
        'salary' => 'Salaires',
        'electricity' => 'Électricité',
        'water' => 'Eau',
        'internet' => 'Internet',
        'transport' => 'Transport',
        'maintenance' => 'Entretien',
        'repair' => 'Réparation',
        'rent' => 'Loyer',
        'other' => 'Autres',
    ];

    protected $fillable = [
        'restaurant_id', 'user_id', 'cash_register_id',
        'category', 'amount', 'description', 'expense_date', 'receipt_path',
        'receipt_source', 'receipt_taken_at',
    ];

    protected $casts = [
        'expense_date' => 'datetime',
        'receipt_taken_at' => 'datetime',
    ];

    /** Une photo choisie dans le téléphone et prise plus de 2 jours avant la dépense : à regarder de près. */
    public function receiptLooksOld(): bool
    {
        return $this->receipt_source === \App\Support\PhotoOrigin::GALLERY
            && $this->receipt_taken_at
            && $this->receipt_taken_at->lt($this->expense_date->copy()->subDays(\App\Support\PhotoOrigin::OLD_AFTER_DAYS));
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }
}