<?php

namespace App\Support;

use App\Models\Category;
use App\Models\CreditTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
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
    /**
     * Ventes séparées Nourriture / Boissons : chaque produit vendu et le total de chaque famille.
     *
     * @var Collection<string, object{kind: string, label: string, amount: int, quantity: int, margin: ?int, items: Collection}>
     */
    public readonly Collection $byKind;
    /** @var Collection<int, object{label: string, by: string, reason: ?string, where: ?string, amount: int}> */
    public readonly Collection $gestures;
    public readonly int $gesturesTotal;
    public readonly int $cancelledCount;
    public readonly int $cancelledTotal;
    /** Coût d'achat des articles vendus (ou offerts) sur la période. */
    public readonly int $costOfGoods;
    /** Articles vendus dont le prix d'achat est inconnu : bénéfice surestimé. */
    public readonly int $uncostedQuantity;

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

        $this->byKind = self::salesByKind($settledIds);

        // Coût des produits vendus (prix d'achat figé à la vente) ; les articles
        // offerts comptent aussi : ils ont été achetés et consommés
        $costs = OrderItem::query()
            ->whereIn('order_id', $settledIds)
            ->selectRaw('COALESCE(SUM(CASE WHEN unit_cost IS NOT NULL THEN quantity * unit_cost ELSE 0 END), 0) as cogs')
            ->selectRaw('COALESCE(SUM(CASE WHEN unit_cost IS NULL THEN quantity ELSE 0 END), 0) as uncosted')
            ->first();
        $this->costOfGoods = (int) $costs->cogs;
        $this->uncostedQuantity = (int) $costs->uncosted;

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

    /**
     * Valeur des pertes de stock (casse, périmé) et des écarts d'inventaire
     * sur une période. Positif = perte pour le maquis.
     */
    public static function stockLosses(int $restaurantId, CarbonInterface $from, CarbonInterface $to): int
    {
        return -(int) \App\Models\StockMovement::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('type', ['loss', 'inventory'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(quantity * COALESCE(unit_cost, 0)), 0) as value')
            ->value('value');
    }

    /**
     * Nourriture d'abord, puis boissons. La famille vient de la catégorie du
     * produit (réglée dans « Produits »). Les articles offerts ne comptent pas
     * comme vendus. Le bénéfice n'est donné que si tous les prix d'achat sont connus.
     */
    private static function salesByKind(Collection $orderIds): Collection
    {
        $rows = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->where('is_offered', false)
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(total) as amount')
            ->selectRaw('SUM(CASE WHEN unit_cost IS NOT NULL THEN quantity * unit_cost ELSE 0 END) as cost')
            ->selectRaw('SUM(CASE WHEN unit_cost IS NULL THEN 1 ELSE 0 END) as uncosted')
            ->groupBy('product_id')
            ->get();

        $products = Product::withoutGlobalScopes()
            ->with(['category' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereIn('id', $rows->pluck('product_id'))
            ->get(['id', 'name', 'category_id'])
            ->keyBy('id');

        $kinds = collect(['food' => collect(), 'drink' => collect()]);

        foreach ($rows as $row) {
            $product = $products->get($row->product_id);
            $kind = $product?->category?->kind === 'drink' ? 'drink' : 'food';
            $kinds[$kind]->push((object) [
                'name' => $product?->name ?? '—',
                'quantity' => (int) $row->qty,
                'amount' => (int) $row->amount,
                'cost' => (int) $row->cost,
                'uncosted' => (int) $row->uncosted > 0,
            ]);
        }

        return $kinds->map(function (Collection $items, string $kind) {
            $amount = (int) $items->sum('amount');

            return (object) [
                'kind' => $kind,
                'label' => Category::KINDS[$kind],
                'amount' => $amount,
                'quantity' => (int) $items->sum('quantity'),
                'margin' => $items->contains('uncosted', true) ? null : $amount - (int) $items->sum('cost'),
                'items' => $items->sortByDesc('amount')->values(),
            ];
        });
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
