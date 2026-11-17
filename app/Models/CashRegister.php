<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'restaurant_id', 'opened_by', 'closed_by',
        'opening_amount', 'theoretical_amount', 'counted_amount', 'discrepancy',
        'closing_note', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    protected function cashPaymentsQuery()
    {
        return Payment::where('payment_method', 'cash')
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$this->opened_at, $this->closed_at ?? now()])
            ->whereHas('order', fn ($q) => $q->where('restaurant_id', $this->restaurant_id));
    }

    public function calculateTheoreticalAmount(): int
    {
        $received = $this->cashPaymentsQuery()->sum('amount_received');
        $changeGiven = $this->cashPaymentsQuery()->sum('change_given');
        $expensesTotal = $this->expenses()->sum('amount');

        return $this->opening_amount + $received - $changeGiven - $expensesTotal;
    }

    /**
     * Décomposition détaillée du solde théorique, pour affichage
     * pédagogique avant/après clôture (au lieu d'un chiffre brut).
     */
    public function breakdown(): array
    {
        $paymentsCount = $this->cashPaymentsQuery()->count();
        $received = $this->cashPaymentsQuery()->sum('amount_received');
        $changeGiven = $this->cashPaymentsQuery()->sum('change_given');
        $expensesCount = $this->expenses()->count();
        $expensesTotal = $this->expenses()->sum('amount');

        return [
            'opening_amount' => $this->opening_amount,
            'payments_count' => $paymentsCount,
            'received' => $received,
            'change_given' => $changeGiven,
            'expenses_count' => $expensesCount,
            'expenses_total' => $expensesTotal,
            'theoretical' => $this->opening_amount + $received - $changeGiven - $expensesTotal,
        ];
    }

    public function close(int $countedAmount, ?int $closedBy = null, ?string $note = null): void
    {
        $theoretical = $this->calculateTheoreticalAmount();

        $this->update([
            'theoretical_amount' => $theoretical,
            'counted_amount' => $countedAmount,
            'discrepancy' => $countedAmount - $theoretical,
            'closing_note' => $note,
            'closed_by' => $closedBy,
            'closed_at' => now(),
        ]);
    }
}