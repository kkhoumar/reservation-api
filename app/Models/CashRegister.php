<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use BelongsToRestaurant, HasFactory;

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

    public function remittances(): HasMany
    {
        return $this->hasMany(CashRemittance::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /**
     * Paiements espèces encaissés directement à la caisse.
     * Ceux encaissés par une serveuse (sacoche) n'y entrent qu'au versement.
     */
    protected function cashPaymentsQuery()
    {
        // Part espèces des paiements (100 % espèces ou mixtes)
        return $this->sessionPaymentsQuery()
            ->where('in_sacoche', false)
            ->where('amount_received', '>', 0);
    }

    protected function sessionPaymentsQuery()
    {
        return Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$this->opened_at, $this->closed_at ?? now()])
            ->whereHas('order', fn ($q) => $q->where('restaurant_id', $this->restaurant_id));
    }

    /**
     * Mobile Money reçu pendant la session, par opérateur. Il n'est pas dans
     * le tiroir : on le vérifie sur les téléphones du maquis.
     *
     * @return array<string, int>
     */
    public function mobileMoneyByMethod(): array
    {
        return $this->sessionPaymentsQuery()
            ->where('mobile_amount', '>', 0)
            ->selectRaw('payment_method, SUM(mobile_amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public function calculateTheoreticalAmount(): int
    {
        return $this->breakdown()['theoretical'];
    }

    /**
     * Décomposition détaillée du solde théorique, pour affichage
     * pédagogique avant/après clôture (au lieu d'un chiffre brut).
     */
    public function breakdown(): array
    {
        $paymentsCount = $this->cashPaymentsQuery()->count();
        $received = (int) $this->cashPaymentsQuery()->sum('amount_received');
        $changeGiven = (int) $this->cashPaymentsQuery()->sum('change_given');
        $remittancesCount = $this->remittances()->count();
        $remittancesTotal = (int) $this->remittances()->sum('amount');
        $repayments = CreditTransaction::withoutGlobalScopes()
            ->where('cash_register_id', $this->id)
            ->where('type', 'repayment');
        $repaymentsCount = (clone $repayments)->count();
        $repaymentsTotal = (int) (clone $repayments)->sum('amount');
        $expensesCount = $this->expenses()->count();
        $expensesTotal = (int) $this->expenses()->sum('amount');

        return [
            'opening_amount' => $this->opening_amount,
            'payments_count' => $paymentsCount,
            'received' => $received,
            'change_given' => $changeGiven,
            'remittances_count' => $remittancesCount,
            'remittances_total' => $remittancesTotal,
            'mobile_money' => $mobile = $this->mobileMoneyByMethod(),
            'mobile_total' => array_sum($mobile),
            'repayments_count' => $repaymentsCount,
            'repayments_total' => $repaymentsTotal,
            'expenses_count' => $expensesCount,
            'expenses_total' => $expensesTotal,
            'theoretical' => $this->opening_amount + $received - $changeGiven + $remittancesTotal + $repaymentsTotal - $expensesTotal,
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