<?php

namespace App\Support;

use App\Models\CreditTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Activité d'un maquis sur une période : ventes réglées (encaissées ou à
 * crédit), par serveuse, meilleures ventes, gestes commerciaux, bons annulés.
 *
 * Source commune du rapport du soir (période = session de caisse) et du
 * tableau de bord (aujourd'hui, semaine, mois).
 */
class PeriodStats
{
    public readonly int $paidCount;
    public readonly int $paidTotal;
    public readonly int $creditCount;
    public readonly int $creditTotal;
    /** @var Collection<int, object{name: string, bons: int, amount: int}> */
    public readonly Collection $byServer;
    /** @var Collection<int, object{name: string, quantity: int, amount: int}> */
    public readonly Collection $topProducts;
    /** @var Collection<int, object{label: string, by: string, reason: ?string, where: ?string, amount: int}> */
    public readonly Collection $gestures;
    public readonly int $gesturesTotal;
    public readonly int $cancelledCount;
    public readonly int $cancelledTotal;

    public function __construct(
        public readonly int $restaurantId,
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
        int $topLimit = 3,
    ) {
        $payments = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->whereHas('order', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->get(['id', 'order_id', 'amount_due']);

        $charges = CreditTransaction::query()
            ->where('restaurant_id', $restaurantId)
            ->where('type', 'charge')
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'order_id', 'amount']);

        $this->paidCount = $payments->count();
        $this->paidTotal = (int) $payments->sum('amount_due');
        $this->creditCount = $charges->count();
        $this->creditTotal = (int) $charges->sum('amount');

        $settledIds = $payments->pluck('order_id')->merge($charges->pluck('order_id'))->filter()->unique()->values();

        $this->byServer = Order::query()
            ->with('server:id,first_name')
            ->whereIn('id', $settledIds)
            ->selectRaw('user_id, count(*) as bons, sum(total) as amount')
            ->groupBy('user_id')
            ->orderByDesc('amount')
            ->get()
            ->map(fn (Order $row) => (object) [
                'name' => $row->server?->first_name ?? '—',
                'bons' => (int) $row->bons,
                'amount' => (int) $row->amount,
            ]);

        $this->topProducts = OrderItem::query()
            ->with('product:id,name')
            ->whereIn('order_id', $settledIds)
            ->selectRaw('product_id, sum(quantity) as qty, sum(total) as amount')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit($topLimit)
            ->get()
            ->map(fn (OrderItem $row) => (object) [
                'name' => $row->product?->name ?? '—',
                'quantity' => (int) $row->qty,
                'amount' => (int) $row->amount,
            ]);

        $offered = OrderItem::query()
            ->with(['product:id,name', 'offeredBy:id,first_name', 'order.restaurantTable'])
            ->whereIn('order_id', $settledIds)
            ->where('is_offered', true)
            ->get()
            ->map(fn (OrderItem $i) => (object) [
                'label' => "{$i->quantity} × " . ($i->product?->name ?? '—') . ' offert' . ($i->quantity > 1 ? 's' : ''),
                'by' => $i->offeredBy?->first_name ?? '—',
                'reason' => $i->offered_reason,
                'where' => $i->order?->restaurantTable?->label,
                'amount' => $i->fullValue(),
            ]);

        $discounts = Order::query()
            ->with(['discountedBy:id,first_name', 'restaurantTable'])
            ->whereIn('id', $settledIds)
            ->where('discount_amount', '>', 0)
            ->get()
            ->map(fn (Order $o) => (object) [
                'label' => 'Remise de ' . number_format($o->discount_amount, 0, ',', ' '),
                'by' => $o->discountedBy?->first_name ?? '—',
                'reason' => $o->discount_reason,
                'where' => $o->restaurantTable?->label,
                'amount' => (int) $o->discount_amount,
            ]);

        $this->gestures = $offered->concat($discounts)->sortByDesc('amount')->values();
        $this->gesturesTotal = (int) $this->gestures->sum('amount');

        $cancelled = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'cancelled')
            ->where('total', '>', 0)
            ->whereBetween('updated_at', [$from, $to]);
        $this->cancelledCount = (clone $cancelled)->count();
        $this->cancelledTotal = (int) (clone $cancelled)->sum('total');
    }

    /** Total dû par les clients à crédit (tous les temps). */
    public static function creditDue(int $restaurantId): int
    {
        return (int) CreditTransaction::query()
            ->where('restaurant_id', $restaurantId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'charge' THEN amount ELSE -amount END), 0) as due")
            ->value('due');
    }

    /** Argent encore sur les serveuses (sacoches non versées). */
    public static function sacochesOutstanding(int $restaurantId): int
    {
        return (int) User::where('restaurant_id', $restaurantId)
            ->where('role', 'server')
            ->get()
            ->sum(fn (User $s) => max(0, $s->sacocheBalance()));
    }
}
