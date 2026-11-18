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
        'opening_amount', 'theoretical_amount', 'counted_amount', 'cash_count', 'discrepancy',
        'closing_note', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'cash_count' => 'array',
    ];

    /**
     * Billets et pièces en circulation (FCFA), pour le billetage.
     * Clé => [valeur, libellé].
     */
    public const DENOMINATIONS = [
        'b10000' => [10000, 'Billet 10 000'],
        'b5000' => [5000, 'Billet 5 000'],
        'b2000' => [2000, 'Billet 2 000'],
        'b1000' => [1000, 'Billet 1 000'],
        'b500' => [500, 'Billet 500'],
        'p500' => [500, 'Pièce 500'],
        'p250' => [250, 'Pièce 250'],
        'p200' => [200, 'Pièce 200'],
        'p100' => [100, 'Pièce 100'],
        'p50' => [50, 'Pièce 50'],
        'p25' => [25, 'Pièce 25'],
        'p10' => [10, 'Pièce 10'],
        'p5' => [5, 'Pièce 5'],
    ];

    /**
     * Total d'un billetage (les clés inconnues et les nombres négatifs sont ignorés).
     *
     * @param  array<string, int|string|null>  $counts
     */
    public static function countTotal(array $counts): int
    {
        $total = 0;

        foreach (self::DENOMINATIONS as $key => [$value]) {
            $total += max(0, (int) ($counts[$key] ?? 0)) * $value;
        }

        return $total;
    }

    /**
     * Journal de la session : chaque mouvement, du plus récent au plus ancien.
     * « drawer » indique si l'argent est passé par le tiroir.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function journal(): \Illuminate\Support\Collection
    {
        $from = $this->opened_at;
        $to = $this->closed_at ?? now();
        $rows = collect();
        $row = fn (array $data) => (object) $data;

        $payments = $this->sessionPaymentsQuery()
            ->with(['order.restaurantTable', 'order.server', 'cashier'])
            ->get();

        foreach ($payments as $p) {
            $detail = collect([
                $p->order?->order_number,
                $p->order?->restaurantTable?->label ?? 'À emporter',
                $p->order?->server ? 'servi par ' . $p->order->server->first_name : null,
            ])->filter()->join(' · ');

            $cash = $p->cashPart();

            if ($cash > 0 && ! $p->in_sacoche) {
                $rows->push($row([
                    'at' => $p->paid_at, 'kind' => 'sale',
                    'label' => $p->isMixed() ? 'Vente (part espèces)' : 'Vente en espèces',
                    'detail' => $detail . ($p->change_given > 0 ? ' · reçu ' . number_format($p->amount_received, 0, ',', ' ') . ', rendu ' . number_format($p->change_given, 0, ',', ' ') : ''),
                    'by' => $p->cashier?->first_name, 'amount' => $cash, 'drawer' => true,
                ]));
            }

            if ($cash > 0 && $p->in_sacoche) {
                $rows->push($row([
                    'at' => $p->paid_at, 'kind' => 'sacoche',
                    'label' => 'Vente encaissée par la serveuse (sacoche)',
                    'detail' => $detail, 'by' => $p->cashier?->first_name, 'amount' => $cash, 'drawer' => false,
                ]));
            }

            if ($p->mobile_amount > 0) {
                $rows->push($row([
                    'at' => $p->paid_at, 'kind' => 'mobile',
                    'label' => 'Vente ' . (Payment::METHODS[$p->payment_method] ?? 'Mobile Money'),
                    'detail' => $detail . ($p->reference ? ' · réf. ' . $p->reference : ''),
                    'by' => $p->cashier?->first_name, 'amount' => (int) $p->mobile_amount, 'drawer' => false,
                ]));
            }

            if ($p->amount_due === 0) {
                $rows->push($row([
                    'at' => $p->paid_at, 'kind' => 'offered', 'label' => 'Bon entièrement offert',
                    'detail' => $detail, 'by' => $p->cashier?->first_name, 'amount' => 0, 'drawer' => false,
                ]));
            }
        }

        foreach ($this->remittances()->with(['server', 'receiver'])->get() as $r) {
            $rows->push($row([
                'at' => $r->created_at, 'kind' => 'remittance',
                'label' => 'Versement de ' . ($r->server?->first_name ?? '—'),
                'detail' => $r->note, 'by' => $r->receiver?->first_name, 'amount' => (int) $r->amount, 'drawer' => true,
            ]));
        }

        $credits = CreditTransaction::withoutGlobalScopes()
            ->with(['customer', 'recordedBy', 'order.restaurantTable'])
            ->where('restaurant_id', $this->restaurant_id)
            ->where(fn ($q) => $q
                ->where(fn ($r) => $r->where('type', 'repayment')->where('cash_register_id', $this->id))
                ->orWhere(fn ($c) => $c->where('type', 'charge')->whereBetween('created_at', [$from, $to])))
            ->get();

        foreach ($credits as $t) {
            $repayment = $t->type === 'repayment';
            $rows->push($row([
                'at' => $t->created_at, 'kind' => $repayment ? 'repayment' : 'credit',
                'label' => ($repayment ? 'Remboursement de crédit — ' : 'Vente à crédit — ') . ($t->customer?->name ?? '—'),
                'detail' => $repayment
                    ? $t->note
                    : collect([$t->order?->order_number, $t->order?->restaurantTable?->label])->filter()->join(' · '),
                'by' => $t->recordedBy?->first_name, 'amount' => (int) $t->amount, 'drawer' => $repayment,
            ]));
        }

        foreach ($this->expenses()->with('user')->get() as $e) {
            $rows->push($row([
                'at' => $e->expense_date, 'kind' => 'expense',
                'label' => 'Dépense — ' . ($e->description ?: (Expense::CATEGORIES[$e->category] ?? $e->category)),
                'detail' => Expense::CATEGORIES[$e->category] ?? $e->category,
                'by' => $e->user?->first_name, 'amount' => -(int) $e->amount, 'drawer' => true,
            ]));
        }

        return $rows->sortByDesc('at')->values();
    }

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

    /**
     * @param  array<string, int>|null  $cashCount  billetage (nombre par billet/pièce)
     */
    public function close(int $countedAmount, ?int $closedBy = null, ?string $note = null, ?array $cashCount = null): void
    {
        $theoretical = $this->calculateTheoreticalAmount();

        $this->update([
            'theoretical_amount' => $theoretical,
            'counted_amount' => $countedAmount,
            'cash_count' => $cashCount ? array_filter($cashCount) : null,
            'discrepancy' => $countedAmount - $theoretical,
            'closing_note' => $note,
            'closed_by' => $closedBy,
            'closed_at' => now(),
        ]);
    }
}