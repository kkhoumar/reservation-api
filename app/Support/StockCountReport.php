<?php

namespace App\Support;

use App\Models\Category;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Résultat d'un comptage : totaux séparés boissons / nourriture et message
 * WhatsApp prêt à envoyer au patron.
 */
class StockCountReport
{
    public function __construct(public StockCount $count)
    {
        $count->loadMissing(['lines', 'user', 'restaurant']);
    }

    /**
     * Totaux par famille et au total.
     *
     * @return array<string, array{label: string, products: int, cost: int, sale: int, margin: int, gap_qty: int, gap_cost: int, gap_sale: int, missing_cost: int}>
     */
    public function summary(): array
    {
        $groups = [];

        foreach (Category::KINDS as $kind => $label) {
            $lines = $this->count->lines->where('kind', $kind);

            if ($lines->isNotEmpty()) {
                $groups[$kind] = $this->totals($label, $lines);
            }
        }

        $groups['total'] = $this->totals('Total', $this->count->lines);

        return $groups;
    }

    protected function totals(string $label, Collection $lines): array
    {
        $cost = $lines->sum(fn ($l) => $l->counted * $l->costOrPrice());
        $sale = $lines->sum(fn ($l) => $l->counted * $l->unit_price);

        return [
            'label' => $label,
            'products' => $lines->count(),
            'cost' => $cost,
            'sale' => $sale,
            'margin' => $sale - $cost,
            'gap_qty' => $lines->sum('gap'),
            'gap_cost' => $lines->sum(fn ($l) => $l->gap * $l->costOrPrice()),
            'gap_sale' => $lines->sum(fn ($l) => $l->gap * $l->unit_price),
            'missing_cost' => $lines->whereNull('unit_cost')->count(),
            'unbilled' => $lines->sum(fn ($l) => $l->unbilled * $l->unit_price),
        ];
    }

    public static function fcfa(int $n): string
    {
        return ($n < 0 ? '-' : '') . number_format(abs($n), 0, ',', ' ') . ' F';
    }

    /** Écart avec son signe : « +3 500 F », « -2 000 F », « 0 F ». */
    public static function signed(int $n): string
    {
        return ($n > 0 ? '+' : '') . self::fcfa($n);
    }

    public function toWhatsappText(): string
    {
        $c = $this->count;
        $date = $c->created_at->locale('fr');
        $out = [];

        $out[] = '📦 *Comptage du stock · ' . ($c->restaurant?->name ?? 'Maquis') . '*';
        $out[] = ucfirst($date->isoFormat('dddd D MMMM YYYY [à] HH[h]mm')) . ($c->user ? ', par ' . $c->user->first_name : '');
        $out[] = $c->previous_count_at
            ? 'Période : ' . $this->periodLabel() . ' depuis le comptage du ' . $c->previous_count_at->locale('fr')->isoFormat('D MMMM')
            : 'Premier comptage';

        foreach ($this->summary() as $kind => $g) {
            $out[] = '';
            $out[] = '*' . $g['label'] . '* · ' . $g['products'] . ' ' . ($g['products'] > 1 ? 'produits comptés' : 'produit compté');

            if ($kind !== 'total') {
                $gaps = $c->lines->where('kind', $kind)->where('gap', '!=', 0);

                foreach ($gaps as $l) {
                    $out[] = '• ' . $l->product_name . ' : ' . ($l->gap > 0 ? '+' : '') . $l->gap
                        . ' (' . self::signed($l->gap * $l->unit_price) . ')'
                        . ($l->reasonLabel() ? ' · ' . mb_strtolower($l->reasonLabel()) : '')
                        . ($l->unbilled ? " · {$l->unbilled} vendue" . ($l->unbilled > 1 ? 's' : '') . ' sans bon' : '')
                        . ($l->taken_out ? " · {$l->taken_out} sortie" . ($l->taken_out > 1 ? 's' : '') . ' du maquis' : '');
                }

                $out[] = $gaps->isEmpty() ? '• Tout est juste ✅' : '• Écart : ' . self::signed($g['gap_sale']) . ($g['gap_sale'] < 0 ? ' ⚠️' : '');
            } else {
                $out[] = '• Stock au prix d\'achat : ' . self::fcfa($g['cost']);
                $out[] = '• Stock au prix de vente : ' . self::fcfa($g['sale']);
                $out[] = '• Bénéfice possible : ' . self::fcfa($g['margin']);
                $out[] = '• Écart : ' . self::signed($g['gap_sale']) . ($g['gap_sale'] < 0 ? ' ⚠️' : ($g['gap_sale'] === 0 ? ' ✅' : ''));
                if ($g['unbilled'] > 0) {
                    $out[] = '• Vendu sans bon (vides en trop) : ' . self::fcfa($g['unbilled']) . ' ⚠️';
                }
            }
        }

        if ($c->note) {
            $out[] = '';
            $out[] = 'Note : ' . $c->note;
        }

        return implode("\n", $out);
    }

    public function periodLabel(): string
    {
        if (! $this->count->previous_count_at) {
            return '';
        }

        $days = (int) $this->count->previous_count_at->copy()->startOfDay()->diffInDays($this->count->created_at->copy()->startOfDay());

        return $days === 0 ? 'le même jour' : $days . ' ' . ($days > 1 ? 'jours' : 'jour');
    }

    public function whatsappToOwnerUrl(): ?string
    {
        $phone = $this->count->restaurant?->reportRecipientPhone();

        return $phone
            ? 'https://wa.me/225' . User::normalizePhone($phone) . '?text=' . rawurlencode($this->toWhatsappText())
            : null;
    }

    public function whatsappShareUrl(): string
    {
        return 'https://wa.me/?text=' . rawurlencode($this->toWhatsappText());
    }
}
