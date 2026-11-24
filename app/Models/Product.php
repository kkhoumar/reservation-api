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
        'restaurant_id', 'category_id', 'name', 'photo',
        'selling_price', 'cost_price', 'is_available', 'stock_quantity', 'stock_alert_threshold',
        'is_returnable', 'crate_size', 'empty_quantity', 'empties_counted_at',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'archived_at' => 'datetime',
        'cost_price' => 'integer',
        'selling_price' => 'integer',
        'stock_quantity' => 'integer',
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

    public function isLowStock(): bool
    {
        return $this->stock_alert_threshold !== null
            && $this->stock_quantity <= $this->stock_alert_threshold;
    }

    public function decrementStock(int $quantity): void
    {
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
        static::whereKey($productId)->increment('stock_quantity', $quantity);
        static::whereKey($productId)->where('is_returnable', true)->decrement('empty_quantity', $quantity);
    }

    /** Le maquis a déjà compté les vides de ce produit : le contrôle des vides est possible. */
    public function hasEmptiesBaseline(): bool
    {
        return $this->is_returnable && $this->empties_counted_at !== null;
    }
}