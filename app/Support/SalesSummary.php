<?php

namespace App\Support;

use App\Models\CreditTransaction;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Ventes réglées sur une période, quel que soit le moyen :
 * espèces, Mobile Money (par opérateur) ou ardoise.
 *
 * Source unique pour le tableau de bord, les ventes du jour et le rapport du soir.
 */
class SalesSummary
{
    public int $cash = 0;
    /** @var array<string, int> opérateur => montant */
    public array $mobile = [];
    public int $credit = 0;
    public int $paidCount = 0;
    public int $creditCount = 0;

    public function __construct(int $restaurantId, CarbonInterface $from, CarbonInterface $to)
    {
        $payments = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->whereHas('order', fn ($q) => $q->where('restaurant_id', $restaurantId));

        $totals = (clone $payments)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(amount_due - mobile_amount), 0) as cash')
            ->first();

        $this->paidCount = (int) $totals->n;
        $this->cash = (int) $totals->cash;

        $this->mobile = (clone $payments)
            ->where('mobile_amount', '>', 0)
            ->select('payment_method', DB::raw('SUM(mobile_amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn ($v) => (int) $v)
            ->all();

        $charges = CreditTransaction::query()
            ->where('restaurant_id', $restaurantId)
            ->where('type', 'charge')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(amount), 0) as total')
            ->first();

        $this->creditCount = (int) $charges->n;
        $this->credit = (int) $charges->total;
    }

    public function mobileTotal(): int
    {
        return array_sum($this->mobile);
    }

    /** Argent reçu (espèces + Mobile Money). */
    public function collected(): int
    {
        return $this->cash + $this->mobileTotal();
    }

    /** Chiffre d'affaires : tout ce qui a été vendu, ardoise comprise. */
    public function total(): int
    {
        return $this->collected() + $this->credit;
    }

    public function count(): int
    {
        return $this->paidCount + $this->creditCount;
    }
}
