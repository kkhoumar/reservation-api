<?php

namespace App\Support;

use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Rapport d'une session de caisse (le « rapport du soir » envoyé au patron).
 *
 * Toutes les ventes réglées pendant la session sont comptées : encaissées
 * en espèces ou mises sur l'ardoise. Les chiffres de caisse viennent du
 * même calcul que la clôture (CashRegister::breakdown()).
 */
class RegisterReport
{
    public readonly int $cashSalesCount;
    public readonly int $cashSales;
    public readonly int $creditSalesCount;
    public readonly int $creditSales;
    /** @var Collection<int, object{label: string, by: string, reason: ?string, where: ?string, amount: int}> */
    public readonly Collection $gestures;
    public readonly int $gesturesTotal;
    public readonly int $cancelledCount;
    public readonly int $cancelledTotal;
    public readonly int $ardoiseDue;
    public readonly int $sacochesOutstanding;
    public readonly array $breakdown;
    /** @var Collection<int, object{name: string, bons: int, amount: int}> */
    public readonly Collection $byServer;
    /** @var Collection<int, object{name: string, quantity: int, amount: int}> */
    public readonly Collection $topProducts;
    /** Nourriture / Boissons : chaque produit vendu et le total de chaque famille. */
    public readonly Collection $byKind;

    public function __construct(public readonly CashRegister $register)
    {
        $restaurantId = $register->restaurant_id;
        $stats = new PeriodStats($restaurantId, $register->opened_at, $register->closed_at ?? now());

        $this->cashSalesCount = $stats->paidCount;
        $this->cashSales = $stats->paidTotal;
        $this->creditSalesCount = $stats->creditCount;
        $this->creditSales = $stats->creditTotal;
        $this->byServer = $stats->byServer;
        $this->topProducts = $stats->topProducts;
        $this->byKind = $stats->byKind;
        $this->gestures = $stats->gestures;
        $this->gesturesTotal = $stats->gesturesTotal;
        $this->cancelledCount = $stats->cancelledCount;
        $this->cancelledTotal = $stats->cancelledTotal;
        $this->ardoiseDue = PeriodStats::creditDue($restaurantId);
        $this->sacochesOutstanding = PeriodStats::sacochesOutstanding($restaurantId);

        $this->breakdown = $register->breakdown();
    }

    public function totalSales(): int
    {
        return $this->cashSales + $this->creditSales;
    }

    public function totalBons(): int
    {
        return $this->cashSalesCount + $this->creditSalesCount;
    }

    /**
     * Valeurs des variables {{1}} à {{10}} du modèle WhatsApp « rapport_caisse »
     * (envoi automatique). Une seule ligne par variable : règle de Meta.
     *
     * @return list<string>
     */
    public function templateParameters(): array
    {
        $r = $this->register;
        $f = fn (int $n) => number_format($n, 0, ',', ' ');
        $sales = new SalesSummary($r->restaurant_id, $r->opened_at, $r->closed_at ?? now());

        $session = ucfirst(Carbon::parse($r->closed_at ?? now())->locale('fr')->isoFormat('dddd D MMMM'))
            . ', caisse ' . $r->opened_at->format('H:i') . ' → ' . ($r->closed_at?->format('H:i') ?? 'en cours')
            . ($r->closedByUser ? ', clôturée par ' . $r->closedByUser->first_name : '');

        $diff = (int) $r->discrepancy;
        $gap = $r->closed_at
            ? ($diff > 0 ? '+' : '') . $f($diff) . ' FCFA' . ($diff === 0 ? ' ✅' : ' ⚠️') . ($r->closing_note ? ' (' . $r->closing_note . ')' : '')
            : '—';

        $alerts = collect([
            $this->cancelledCount > 0 ? $this->cancelledCount . ($this->cancelledCount > 1 ? ' bons annulés : ' : ' bon annulé : ') . $f($this->cancelledTotal) . ' F' : null,
            $this->gesturesTotal > 0 ? 'offerts et remises : ' . $f($this->gesturesTotal) . ' F' : null,
            $this->sacochesOutstanding > 0 ? 'sacoches non versées : ' . $f($this->sacochesOutstanding) . ' F' : null,
            $this->ardoiseDue > 0 ? 'crédits à récupérer : ' . $f($this->ardoiseDue) . ' F' : null,
        ])->filter()->join(' · ');

        return [
            $r->restaurant->name,
            $session,
            $f($this->totalSales()),
            (string) $this->totalBons(),
            $f($sales->cash),
            $f($sales->mobileTotal()),
            $f($sales->credit),
            $f((int) $this->breakdown['expenses_total']),
            $gap,
            $alerts !== '' ? $alerts : 'rien à signaler',
        ];
    }

    /**
     * Le rapport en texte, mis en forme pour WhatsApp (*gras*).
     */
    public function toWhatsappText(): string
    {
        $r = $this->register;
        $f = fn (int $n) => number_format($n, 0, ',', ' ');
        $date = ucfirst(Carbon::parse($r->closed_at ?? now())->locale('fr')->isoFormat('dddd D MMMM'));

        $lines = [];
        $lines[] = "*{$r->restaurant->name}* — rapport du {$date}";
        $lines[] = 'Caisse ' . $r->opened_at->format('H:i') . ' → ' . ($r->closed_at?->format('H:i') ?? 'en cours')
            . ($r->closedByUser ? ' (clôturée par ' . $r->closedByUser->first_name . ')' : '');
        $lines[] = '';
        $lines[] = '*Ventes : ' . $f($this->totalSales()) . ' FCFA* (' . $this->totalBons() . ' bons)';
        $sales = new SalesSummary($r->restaurant_id, $r->opened_at, $r->closed_at ?? now());
        $lines[] = '• Espèces : ' . $f($sales->cash);
        foreach ($sales->mobile as $method => $amount) {
            $lines[] = '• ' . (Payment::METHODS[$method] ?? $method) . ' : ' . $f($amount);
        }
        if ($this->creditSales > 0) {
            $lines[] = '• À crédit : ' . $f($this->creditSales);
        }
        $lines[] = '• Dépenses : ' . $f($this->breakdown['expenses_total']);
        if (($this->breakdown['added_total'] ?? 0) > 0) {
            $lines[] = '• Argent ajouté au tiroir : +' . $f($this->breakdown['added_total']);
        }
        if (($this->breakdown['removed_total'] ?? 0) > 0) {
            $lines[] = '• Argent retiré du tiroir : −' . $f($this->breakdown['removed_total']);
        }
        if ($this->breakdown['repayments_total'] > 0) {
            $lines[] = '• Remboursements de crédits : ' . $f($this->breakdown['repayments_total']);
        }

        if ($r->closed_at) {
            $diff = (int) $r->discrepancy;
            $lines[] = '';
            $lines[] = '*Caisse*';
            $lines[] = '• Attendu : ' . $f((int) $r->theoretical_amount);
            $lines[] = '• Compté : ' . $f((int) $r->counted_amount);
            $lines[] = '• Écart : ' . ($diff > 0 ? '+' : '') . $f($diff) . ($diff === 0 ? ' ✅' : ' ⚠️');
            if ($r->closing_note) {
                $lines[] = '• Note : ' . $r->closing_note;
            }
        }

        if ($this->byServer->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '*Par serveuse*';
            foreach ($this->byServer as $row) {
                $lines[] = "• {$row->name} : {$row->bons} bon" . ($row->bons > 1 ? 's' : '') . ' · ' . $f($row->amount);
            }
        }

        // Ce qui a été vendu, cuisine et bar séparés
        foreach ($this->byKind as $group) {
            if ($group->items->isEmpty()) {
                continue;
            }
            $show = $group->kind === 'food' ? 15 : 10;
            $lines[] = '';
            $lines[] = ($group->kind === 'food' ? '🍽️' : '🍺') . ' *' . $group->label . ' : ' . $f($group->amount) . ' FCFA* (' . $group->quantity . ')'
                . ($group->margin !== null ? ' · bénéfice ' . $f($group->margin) : '');
            foreach ($group->items->take($show) as $item) {
                $lines[] = "• {$item->name} ×{$item->quantity} · " . $f($item->amount);
            }
            if ($group->items->count() > $show) {
                $rest = $group->items->slice($show);
                $lines[] = '• … et ' . $rest->count() . ' autre' . ($rest->count() > 1 ? 's' : '') . ' · ' . $f((int) $rest->sum('amount'));
            }
        }

        if ($this->gestures->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '*Offerts et remises : ' . $f($this->gesturesTotal) . ' FCFA*';
            foreach ($this->gestures->take(5) as $g) {
                $lines[] = "• {$g->label} par {$g->by}"
                    . ' (' . collect([$g->reason, $g->where])->filter()->join(', ') . ')';
            }
            if ($this->gestures->count() > 5) {
                $lines[] = '• … et ' . ($this->gestures->count() - 5) . (($this->gestures->count() - 5) > 1 ? ' autres' : ' autre');
            }
        }

        $alerts = [];
        if ($this->cancelledCount > 0) {
            $alerts[] = '• Bons annulés : ' . $this->cancelledCount . ' (' . $f($this->cancelledTotal) . ' FCFA)';
        }
        if ($this->sacochesOutstanding > 0) {
            $alerts[] = '• Sacoches non vidées : ' . $f($this->sacochesOutstanding) . ' FCFA';
        }
        if ($this->ardoiseDue > 0) {
            $alerts[] = '• Crédits clients à récupérer : ' . $f($this->ardoiseDue) . ' FCFA';
        }
        if ($alerts) {
            $lines[] = '';
            $lines[] = '*À surveiller*';
            array_push($lines, ...$alerts);
        }

        $lines[] = '';
        $lines[] = '_Maquis Express_';

        return implode("\n", $lines);
    }

    /**
     * Numéro WhatsApp du patron (premier propriétaire actif du maquis).
     */
    public function ownerPhone(): ?string
    {
        return User::where('restaurant_id', $this->register->restaurant_id)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->orderBy('id')
            ->value('phone');
    }

    public function whatsappToOwnerUrl(): ?string
    {
        $phone = $this->ownerPhone();

        return $phone
            ? 'https://wa.me/225' . User::normalizePhone($phone) . '?text=' . rawurlencode($this->toWhatsappText())
            : null;
    }

    public function whatsappShareUrl(): string
    {
        return 'https://wa.me/?text=' . rawurlencode($this->toWhatsappText());
    }
}
