<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne d'un comptage : la photo d'un produit au moment où il a été
 * compté (les prix et le nom restent ceux du jour, même s'ils changent).
 */
class StockCountLine extends Model
{
    /** Raisons proposées pour expliquer un écart. */
    public const REASONS = [
        'casse' => 'Cassé',
        'offert' => 'Offert',
        'vol' => 'Disparu',
        'erreur' => 'Erreur de saisie',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'stock_count_id', 'product_id', 'product_name', 'kind', 'expected', 'counted', 'gap',
        'unit_cost', 'unit_price', 'delivered', 'sold', 'lost', 'reason',
    ];

    protected $casts = [
        'expected' => 'integer',
        'counted' => 'integer',
        'gap' => 'integer',
        'unit_cost' => 'integer',
        'unit_price' => 'integer',
        'delivered' => 'integer',
        'sold' => 'integer',
        'lost' => 'integer',
    ];

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Prix d'achat, ou prix de vente s'il manque (pour chiffrer). */
    public function costOrPrice(): int
    {
        return (int) ($this->unit_cost ?? $this->unit_price);
    }

    public function reasonLabel(): ?string
    {
        return $this->reason ? (self::REASONS[$this->reason] ?? null) : null;
    }

    /** Stock au comptage précédent (recalculé : attendu − entrées + sorties). */
    public function startQuantity(): int
    {
        return $this->expected - $this->delivered + $this->sold + $this->lost;
    }
}
