<?php

namespace App\Support;

use App\Models\CashRegister;
use App\Models\Product;
use App\Models\StockCount;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Feuille de comptage : pour chaque produit, ce que l'application attend
 * (son stock) et ce qui a bougé depuis le comptage précédent
 * (livraisons, ventes, pertes).
 */
class StockCountSheet
{
    public ?StockCount $previous;

    public ?CarbonInterface $since;

    public function __construct(public int $restaurantId)
    {
        $this->previous = StockCount::withoutGlobalScopes()
            ->with('user')
            ->where('restaurant_id', $restaurantId)
            ->latest('created_at')->latest('id')
            ->first();

        $this->since = $this->previous?->created_at;
    }

    /** Jours écoulés depuis le dernier comptage (null s'il n'y en a jamais eu). */
    public function daysSince(): ?int
    {
        return $this->since ? (int) $this->since->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
    }

    /** Quantités livrées (et stock de départ) par produit depuis le dernier comptage. */
    public function delivered(): Collection
    {
        return $this->movementSums(['delivery', 'initial']);
    }

    /** Quantités perdues (casse, périmé…) par produit, en positif. */
    public function lost(): Collection
    {
        return $this->movementSums(['loss'])->map(fn ($q) => -$q);
    }

    /** Quantités vendues par produit (commandes non annulées). */
    public function sold(): Collection
    {
        if (! $this->since) {
            return collect();
        }

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.restaurant_id', $this->restaurantId)
            ->where('orders.status', '!=', 'cancelled')
            ->where('order_items.created_at', '>', $this->since)
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) AS qty')
            ->pluck('qty', 'product_id')
            ->map(fn ($q) => (int) $q);
    }

    protected function movementSums(array $types): Collection
    {
        if (! $this->since) {
            return collect();
        }

        return DB::table('stock_movements')
            ->where('restaurant_id', $this->restaurantId)
            ->whereIn('type', $types)
            ->where('created_at', '>', $this->since)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) AS qty')
            ->pluck('qty', 'product_id')
            ->map(fn ($q) => (int) $q);
    }

    /**
     * Une ligne par produit, boissons d'abord, dans l'ordre des catégories.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        $delivered = $this->delivered();
        $sold = $this->sold();
        $lost = $this->lost();

        return Product::withoutGlobalScopes()
            ->with(['category' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('restaurant_id', $this->restaurantId)
            ->get()
            ->sortBy(fn (Product $p) => [
                ($p->category?->kind ?? 'food') === 'drink' ? 0 : 1,
                $p->category?->sort_order ?? 999,
                mb_strtolower($p->name),
            ])
            ->values()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->name,
                'kind' => $p->category?->kind ?? 'food',
                'cost' => $p->cost_price,
                'price' => (int) $p->selling_price,
                'expected' => (int) $p->stock_quantity,
                'delivered' => (int) ($delivered[$p->id] ?? 0),
                'sold' => (int) ($sold[$p->id] ?? 0),
                'lost' => (int) ($lost[$p->id] ?? 0),
            ]);
    }

    /** Les caisses tenues depuis le dernier comptage : qui travaillait. */
    public function registers(): Collection
    {
        if (! $this->since) {
            return collect();
        }

        return CashRegister::withoutGlobalScopes()
            ->with(['openedByUser', 'closedByUser'])
            ->where('restaurant_id', $this->restaurantId)
            ->where(fn ($q) => $q->where('opened_at', '>', $this->since)->orWhereNull('closed_at')->orWhere('closed_at', '>', $this->since))
            ->orderBy('opened_at')
            ->get();
    }
}
