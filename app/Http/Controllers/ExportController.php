<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\CsvExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports pour le patron, la gérante ou le comptable (CSV ouvrable dans Excel).
 * Les données sont limitées au maquis courant (filtre automatique par maquis).
 */
class ExportController extends Controller
{
    public const EXPORTS = [
        'ventes' => ['Ventes', 'Chaque bon réglé : date, table, serveuse, mode de paiement, montants.'],
        'articles' => ['Articles vendus', 'Le détail produit par produit, avec prix de vente, prix d\'achat et offerts.'],
        'depenses' => ['Dépenses', 'Toutes les dépenses, par catégorie, avec qui les a saisies.'],
        'caisses' => ['Clôtures de caisse', 'Chaque session : fonds, attendu, compté, écart, note.'],
        'credits' => ['Crédits clients', 'Ce que chaque client doit, et tous les mouvements de crédit de la période.'],
        'stock' => ['Stock', 'L\'état du stock aujourd\'hui, et les livraisons, pertes et comptages de la période.'],
    ];

    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('exports', [
            'exports' => self::EXPORTS,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function download(Request $request, string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::EXPORTS), 404);

        [$from, $to] = $this->range($request);
        $restaurant = $request->user()->currentRestaurant();
        $slug = str($restaurant?->name ?? 'maquis')->slug();
        $filename = "{$slug}-{$type}-{$from->format('Y-m-d')}-au-{$to->format('Y-m-d')}.csv";

        return match ($type) {
            'ventes' => $this->sales($filename, $from, $to),
            'articles' => $this->items($filename, $from, $to),
            'depenses' => $this->expenses($filename, $from, $to),
            'caisses' => $this->registers($filename, $from, $to),
            'credits' => $this->credits($filename, $from, $to),
            'stock' => $this->stock($filename, $from, $to),
        };
    }

    /** Période demandée (par défaut : le mois en cours), 2 ans au plus. */
    protected function range(Request $request): array
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->lt($to->copy()->subYears(2))) {
            $from = $to->copy()->subYears(2)->startOfDay();
        }

        return [$from, $to];
    }

    protected function sales(string $filename, Carbon $from, Carbon $to): StreamedResponse
    {
        $rows = function () use ($from, $to) {
            $payments = Payment::with(['order.restaurantTable', 'order.server', 'cashier'])
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$from, $to])
                ->orderBy('paid_at')
                ->lazy(500);

            foreach ($payments as $p) {
                yield [
                    $p->paid_at->format('d/m/Y'), $p->paid_at->format('H:i'),
                    $p->order?->order_number, $p->order?->restaurantTable?->label ?? 'À emporter',
                    $p->order?->server?->first_name, $p->cashier?->first_name,
                    $p->methodLabel(), $p->reference,
                    (int) $p->order?->subtotal, (int) $p->order?->discount_amount, (int) $p->amount_due,
                    $p->amount_due - $p->mobile_amount, (int) $p->mobile_amount, 0, null,
                ];
            }

            $charges = CreditTransaction::with(['order.restaurantTable', 'order.server', 'recordedBy', 'customer'])
                ->where('type', 'charge')
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->lazy(500);

            foreach ($charges as $t) {
                yield [
                    $t->created_at->format('d/m/Y'), $t->created_at->format('H:i'),
                    $t->order?->order_number, $t->order?->restaurantTable?->label ?? 'À emporter',
                    $t->order?->server?->first_name, $t->recordedBy?->first_name,
                    'Crédit', null,
                    (int) $t->order?->subtotal, (int) $t->order?->discount_amount, (int) $t->amount,
                    0, 0, (int) $t->amount, $t->customer?->name,
                ];
            }
        };

        return CsvExport::download($filename, [
            'Date', 'Heure', 'Bon', 'Table', 'Serveuse', 'Encaissé par', 'Mode', 'Référence',
            'Sous-total', 'Remise', 'Total', 'Espèces', 'Mobile Money', 'Crédit', 'Client à crédit',
        ], $rows());
    }

    protected function items(string $filename, Carbon $from, Carbon $to): StreamedResponse
    {
        $rows = function () use ($from, $to) {
            $items = OrderItem::with(['product', 'order.payment', 'order.restaurantTable'])
                ->whereHas('order', fn ($q) => $q->where('status', 'paid')->whereBetween('updated_at', [$from, $to]))
                ->orderBy('id')
                ->lazy(500);

            foreach ($items as $i) {
                $at = $i->order?->payment?->paid_at ?? $i->order?->updated_at;

                yield [
                    $at?->format('d/m/Y'), $at?->format('H:i'),
                    $i->order?->order_number, $i->order?->restaurantTable?->label ?? 'À emporter',
                    $i->product?->name, (int) $i->quantity, (int) $i->unit_price, (int) $i->total,
                    $i->unit_cost !== null ? (int) $i->unit_cost : null,
                    $i->unit_cost !== null ? (int) $i->total - $i->quantity * $i->unit_cost : null,
                    $i->is_offered ? 'Oui' : 'Non', $i->offered_reason,
                ];
            }
        };

        return CsvExport::download($filename, [
            'Date', 'Heure', 'Bon', 'Table', 'Produit', 'Quantité', 'Prix unitaire', 'Total facturé',
            'Prix d\'achat unitaire', 'Marge', 'Offert', 'Motif de l\'offert',
        ], $rows());
    }

    protected function expenses(string $filename, Carbon $from, Carbon $to): StreamedResponse
    {
        $rows = function () use ($from, $to) {
            foreach (Expense::with('user')->whereBetween('expense_date', [$from, $to])->orderBy('expense_date')->lazy(500) as $e) {
                yield [
                    $e->expense_date->format('d/m/Y'), $e->expense_date->format('H:i'),
                    Expense::CATEGORIES[$e->category] ?? $e->category, $e->description,
                    (int) $e->amount, $e->user?->first_name, $e->cash_register_id ? 'Caisse' : 'Hors caisse',
                ];
            }
        };

        return CsvExport::download($filename, ['Date', 'Heure', 'Catégorie', 'Description', 'Montant', 'Saisie par', 'Payée par'], $rows());
    }

    protected function registers(string $filename, Carbon $from, Carbon $to): StreamedResponse
    {
        $rows = function () use ($from, $to) {
            $registers = CashRegister::with(['openedByUser', 'closedByUser'])
                ->whereNotNull('closed_at')
                ->whereBetween('closed_at', [$from, $to])
                ->orderBy('closed_at')
                ->lazy(200);

            foreach ($registers as $r) {
                yield [
                    $r->opened_at->format('d/m/Y H:i'), $r->closed_at->format('d/m/Y H:i'),
                    $r->openedByUser?->first_name, $r->closedByUser?->first_name,
                    (int) $r->opening_amount, (int) $r->theoretical_amount, (int) $r->counted_amount,
                    (int) $r->discrepancy, $r->closing_note,
                ];
            }
        };

        return CsvExport::download($filename, [
            'Ouverture', 'Clôture', 'Ouverte par', 'Clôturée par', 'Fonds de départ',
            'Attendu dans le tiroir', 'Compté', 'Écart', 'Note',
        ], $rows());
    }

    protected function credits(string $filename, Carbon $from, Carbon $to): StreamedResponse
    {
        $rows = function () use ($from, $to) {
            // 1. Ce que chaque client doit aujourd'hui
            foreach (Customer::orderBy('name')->get() as $c) {
                yield ['SOLDE', null, $c->name, $c->phone, null, null, $c->balance(), $c->credit_limit, null];
            }

            // 2. Mouvements de la période
            $transactions = CreditTransaction::with(['customer', 'order', 'recordedBy'])
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->lazy(500);

            foreach ($transactions as $t) {
                yield [
                    $t->type === 'charge' ? 'Consommation' : 'Remboursement',
                    $t->created_at->format('d/m/Y H:i'),
                    $t->customer?->name, $t->customer?->phone, $t->order?->order_number,
                    $t->recordedBy?->first_name,
                    $t->type === 'charge' ? (int) $t->amount : -(int) $t->amount,
                    null, $t->note,
                ];
            }
        };

        return CsvExport::download($filename, [
            'Type', 'Date', 'Client', 'Téléphone', 'Bon', 'Par', 'Montant (solde dû ou mouvement)', 'Plafond', 'Note',
        ], $rows());
    }

    protected function stock(string $filename, Carbon $from, Carbon $to): StreamedResponse
    {
        $rows = function () use ($from, $to) {
            // 1. État du stock aujourd'hui
            foreach (Product::with('category')->orderBy('name')->get() as $p) {
                yield [
                    'ÉTAT', now()->format('d/m/Y H:i'), $p->name, $p->category?->name,
                    (int) $p->stock_quantity, $p->cost_price, max(0, $p->stock_quantity) * $p->unitValue(), null, null, null,
                ];
            }

            // 2. Mouvements de la période (hors ventes)
            $movements = StockMovement::with(['product.category', 'user'])
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->lazy(500);

            foreach ($movements as $m) {
                yield [
                    $m->typeLabel(), $m->created_at->format('d/m/Y H:i'), $m->product?->name, $m->product?->category?->name,
                    (int) $m->quantity, $m->unit_cost, $m->unit_cost !== null ? $m->quantity * $m->unit_cost : null,
                    $m->stock_before . ' → ' . $m->stock_after, $m->supplier ?? $m->reason, $m->user?->first_name,
                ];
            }
        };

        return CsvExport::download($filename, [
            'Type', 'Date', 'Produit', 'Catégorie', 'Quantité', 'Prix d\'achat unitaire', 'Valeur',
            'Stock avant → après', 'Fournisseur / motif', 'Par',
        ], $rows());
    }
}
