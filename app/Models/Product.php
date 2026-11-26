<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToRestaurant, HasFactory;

    protected $fillable = [
        'restaurant_id', 'category_id', 'name', 'photo', 'icon',
        'selling_price', 'cost_price', 'is_available', 'stock_quantity', 'stock_alert_threshold',
        'is_returnable', 'crate_size', 'empty_quantity', 'empties_counted_at', 'track_stock',
    ];

    /** Comme la base : un produit suit son stock tant qu'on n'a pas dit le contraire. */
    protected $attributes = [
        'track_stock' => true,
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'archived_at' => 'datetime',
        'cost_price' => 'integer',
        'selling_price' => 'integer',
        'stock_quantity' => 'integer',
        'track_stock' => 'boolean',
        'sold_out_on' => 'date',
        'is_returnable' => 'boolean',
        'crate_size' => 'integer',
        'empty_quantity' => 'integer',
        'empties_counted_at' => 'datetime',
    ];

    /**
     * Valeur d'une unité pour chiffrer une perte ou un écart d'inventaire :
     * le prix d'achat s'il est connu, sinon le prix de vente.
     */
    public function unitValue(): int
    {
        return (int) ($this->cost_price ?? $this->selling_price);
    }

    /** Bénéfice par unité vendue (null si le prix d'achat est inconnu). */
    public function unitMargin(): ?int
    {
        return $this->cost_price === null ? null : $this->selling_price - $this->cost_price;
    }

    /**
     * Petit dessin du produit (bière verte, canette, plat…) : celui choisi par
     * le patron, sinon deviné d'après le nom et la catégorie.
     * On passe la catégorie quand on l'a déjà sous la main (évite une requête par produit).
     */
    public function iconKey(?Category $category = null): string
    {
        $category ??= $this->relationLoaded('category') || $this->category_id ? $this->category : null;

        return \App\Support\ProductIcon::keyFor($this->icon, (string) $this->name, $category?->name, $category?->kind);
    }

    /** Adresse de la vraie photo (null s'il n'y en a pas). Le « v » change à chaque nouvelle photo. */
    public function photoUrl(): ?string
    {
        return $this->photo ? route('products.photo', ['product' => $this->id, 'v' => substr(md5($this->photo), 0, 8)]) : null;
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Produits de la carte (hors produits rangés). */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Le produit a-t-il une histoire (ventes, livraisons, pertes, comptages) ?
     * Si non, on peut l'effacer sans fausser aucun rapport.
     */
    public function hasHistory(): bool
    {
        return $this->orderItems()->exists()
            || $this->stockMovements()->where('type', '!=', 'initial')->exists()
            || StockCountLine::where('product_id', $this->id)->exists();
    }

    /** Dans une commande pas encore payée ni annulée ? */
    public function isInOpenOrder(): bool
    {
        return $this->orderItems()
            ->whereHas('order', fn ($q) => $q->whereIn('status', Order::OPEN_STATUSES))
            ->exists();
    }

    /** La cuisine a dit « fini pour aujourd'hui » : il revient tout seul demain. */
    public function isSoldOutToday(): bool
    {
        return $this->sold_out_on !== null && $this->sold_out_on->isSameDay(today());
    }

    /** Combien on peut encore en vendre (null = sans limite : produit sans suivi de stock). */
    public function sellableQuantity(): ?int
    {
        if ($this->isSoldOutToday()) {
            return 0;
        }

        return $this->track_stock ? max(0, (int) $this->stock_quantity) : null;
    }

    /** Pourquoi on ne peut pas en vendre $quantity (null = c'est possible). */
    public function cannotSellReason(int $quantity): ?string
    {
        if ($this->isSoldOutToday()) {
            return "{$this->name} est fini pour aujourd'hui.";
        }
        if (! $this->track_stock || $this->stock_quantity >= $quantity) {
            return null;
        }

        return $this->stock_quantity <= 0
            ? "Rupture de stock : {$this->name} n'est plus disponible."
            : "Stock insuffisant : {$this->name}. Stock disponible : {$this->stock_quantity}.";
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock_alert_threshold !== null
            && $this->stock_quantity <= $this->stock_alert_threshold;
    }

    public function decrementStock(int $quantity): void
    {
        // Plat sans suivi de stock : la vente est comptée, rien n'est décompté
        if (! $this->track_stock) {
            return;
        }

        $this->decrement('stock_quantity', $quantity);

        // Bouteille consignée : chaque bouteille vendue devient un vide au maquis
        if ($this->is_returnable) {
            $this->increment('empty_quantity', $quantity);
        }
    }

    /**
     * Vente annulée ou article retiré : la bouteille pleine revient, le vide attendu disparaît.
     */
    public static function restoreStock(int $productId, int $quantity): void
    {
        static::whereKey($productId)->where('track_stock', true)->increment('stock_quantity', $quantity);
        static::whereKey($productId)->where('track_stock', true)->where('is_returnable', true)->decrement('empty_quantity', $quantity);
    }

    /** Le maquis a déjà compté les vides de ce produit : le contrôle des vides est possible. */
    public function hasEmptiesBaseline(): bool
    {
        return $this->is_returnable && $this->empties_counted_at !== null;
    }
}