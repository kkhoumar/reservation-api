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
    public readonly int $cancelledCount;
    public readonly int $cancelledTotal;
    public readonly int $ardoiseDue;
    public readonly int $sacochesOutstanding;
    public readonly array $breakdown;
    /** @var Collection<int, object{name: string, bons: int, amount: int}> */
    public readonly Collection $byServer;
    /** @var Collection<int, object{name: string, quantity: int, amount: int}> */
    public readonly Collection $topProducts;

    public function __construct(public readonly CashRegister $register)
    {
        $restaurantId = $register->restaurant_id;
        $from = $register->opened_at;
        $to = $register->closed_at ?? now();

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

        $this->cashSalesCount = $payments->count();
        $this->cashSales = (int) $payments->sum('amount_due');
        $this->creditSalesCount = $charges->count();
        $this->creditSales = (int) $charges->sum('amount');

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
            ->limit(3)
            ->get()
            ->map(fn (OrderItem $row) => (object) [
                'name' => $row->product?->name ?? '—',
                'quantity' => (int) $row->qty,
                'amount' => (int) $row->amount,
            ]);

        $cancelled = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'cancelled')
            ->where('total', '>', 0)
            ->whereBetween('updated_at', [$from, $to]);
        $this->cancelledCount = (clone $cancelled)->count();
        $this->cancelledTotal = (int) (clone $cancelled)->sum('total');

        $this->ardoiseDue = (int) CreditTransaction::query()
            ->where('restaurant_id', $restaurantId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'charge' THEN amount ELSE -amount END), 0) as due")
            ->value('due');

        $this->sacochesOutstanding = (int) User::where('restaurant_id', $restaurantId)
            ->where('role', 'server')
            ->get()
            ->sum(fn (User $s) => max(0, $s->sacocheBalance()));

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
            $lines[] = "• Sur l'ardoise : " . $f($this->creditSales);
        }
        $lines[] = '• Dépenses : ' . $f($this->breakdown['expenses_total']);
        if ($this->breakdown['repayments_total'] > 0) {
            $lines[] = "• Remboursements d'ardoise : " . $f($this->breakdown['repayments_total']);
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

        if ($this->topProducts->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '*Meilleures ventes* : ' . $this->topProducts
                ->map(fn ($p) => "{$p->name} ×{$p->quantity}")
                ->join(', ');
        }

        $alerts = [];
        if ($this->cancelledCount > 0) {
            $alerts[] = '• Bons annulés : ' . $this->cancelledCount . ' (' . $f($this->cancelledTotal) . ' FCFA)';
        }
        if ($this->sacochesOutstanding > 0) {
            $alerts[] = '• Sacoches non vidées : ' . $f($this->sacochesOutstanding) . ' FCFA';
        }
        if ($this->ardoiseDue > 0) {
            $alerts[] = '• Ardoise totale due : ' . $f($this->ardoiseDue) . ' FCFA';
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
