<?php

namespace App\Http\Controllers;

use App\Livewire\CashRegisterManage;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\CsvExport;
use App\Support\PeriodStats;
use App\Support\PhotoOrigin;
use App\Support\SalesSummary;
use App\Support\XlsxWriter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exports pour le patron, la gérante ou le comptable.
 *
 * - Par défaut un vrai fichier Excel (.xlsx) : il s'ouvre bien partout,
 *   téléphone compris. Le CSV reste disponible (?format=csv).
 * - « Tout » : un seul fichier avec un onglet par sujet et un Résumé
 *   calculé exactement comme le tableau de bord.
 * - Les données sont limitées au maquis courant (filtre automatique par maquis).
 */
class ExportController extends Controller
{
    public const EXPORTS = [
        'ventes' => ['Ventes', 'Chaque bon réglé : date, table, serveuse, mode de paiement, montants.'],
        'articles' => ['Articles vendus', 'Le détail produit par produit, avec prix de vente, prix d\'achat et offerts.'],
        'depenses' => ['Dépenses', 'Toutes les dépenses, par catégorie, avec qui les a saisies et le reçu.'],
        'caisses' => ['Clôtures de caisse', 'Chaque session : fonds, argent ajouté ou retiré, attendu, compté, écart.'],
        'tiroir' => ['Argent du tiroir', 'L\'argent ajouté ou retiré pendant la caisse, avec le motif et qui l\'a fait.'],
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

    public function download(Request $request, string $type): Response
    {
        abort_unless($type === 'tout' || array_key_exists($type, self::EXPORTS), 404);

        [$from, $to] = $this->range($request);
        $restaurant = $request->user()->currentRestaurant();
        $csv = $request->query('format') === 'csv' && $type !== 'tout';
        $base = str($restaurant?->name ?? 'maquis')->slug() . "-{$type}-{$from->format('Y-m-d')}-au-{$to->format('Y-m-d')}";

        if ($csv) {
            return CsvExport::downloadSheets("{$base}.csv", $this->sheets($type, $from, $to));
        }

        $xlsx = new XlsxWriter;

        if ($type === 'tout') {
            $xlsx->addSummary('Résumé', $this->summary($restaurant, $from, $to));
            foreach (array_keys(self::EXPORTS) as $t) {
                foreach ($this->sheets($t, $from, $to) as $sheet) {
                    $xlsx->addTable($sheet['title'], $sheet['columns'], $sheet['rows']);
                }
            }
        } else {
            foreach ($this->sheets($type, $from, $to) as $sheet) {
                $xlsx->addTable($sheet['title'], $sheet['columns'], $sheet['rows']);
            }
        }

        return response()->download($xlsx->save(), "{$base}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
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

    /**
     * Les tableaux d'un export. Colonnes : [libellé, type] avec type
     * text | int | money | date | time | datetime.
     *
     * @return list<array{title: string, columns: list<array{0: string, 1: string}>, rows: iterable}>
     */
    protected function sheets(string $type, Carbon $from, Carbon $to): array
    {
        return match ($type) {
            'ventes' => [$this->sales($from, $to)],
            'articles' => [$this->items($from, $to)],
            'depenses' => [$this->expenses($from, $to)],
            'caisses' => [$this->registers($from, $to)],
            'tiroir' => [$this->drawer($from, $to)],
            'credits' => $this->credits($from, $to),
            'stock' => $this->stock($from, $to),
        };
    }

    protected function sales(Carbon $from, Carbon $to): array
    {
        $rows = function () use ($from, $to) {
            $payments = Payment::with(['order.restaurantTable', 'order.server', 'cashier'])
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$from, $to])
                ->orderBy('paid_at')
                ->lazy(500);

            foreach ($payments as $p) {
                yield [
                    $p->paid_at, $p->paid_at,
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
                    $t->created_at, $t->created_at,
                    $t->order?->order_number, $t->order?->restaurantTable?->label ?? 'À emporter',
                    $t->order?->server?->first_name, $t->recordedBy?->first_name,
                    'Crédit', null,
                    (int) $t->order?->subtotal, (int) $t->order?->discount_amount, (int) $t->amount,
                    0, 0, (int) $t->amount, $t->customer?->name,
                ];
            }
        };

        return ['title' => 'Ventes', 'rows' => $rows(), 'columns' => [
            ['Date', 'date'], ['Heure', 'time'], ['Bon', 'text'], ['Table', 'text'], ['Serveuse', 'text'],
            ['Encaissé par', 'text'], ['Mode', 'text'], ['Référence', 'text'],
            ['Sous-total', 'money'], ['Remise', 'money'], ['Total', 'money'], ['Espèces', 'money'],
            ['Mobile Money', 'money'], ['Crédit', 'money'], ['Client à crédit', 'text'],
        ]];
    }

    /**
     * Articles des bons réglés sur la période, datés comme les ventes (jour du
     * paiement, ou du crédit) : les totaux concordent avec « Ventes » et le tableau de bord.
     */
    protected function items(Carbon $from, Carbon $to): array
    {
        $rows = function () use ($from, $to) {
            $dates = Payment::where('status', 'completed')->whereBetween('paid_at', [$from, $to])->pluck('paid_at', 'order_id');
            $charged = CreditTransaction::where('type', 'charge')->whereNotNull('order_id')->whereBetween('created_at', [$from, $to])->pluck('created_at', 'order_id');
            foreach ($charged as $orderId => $at) {
                $dates[$orderId] ??= $at;
            }

            $orderIds = $dates->map(fn ($d) => Carbon::parse($d))->sort()->keys();

            foreach ($orderIds->chunk(500) as $chunk) {
                $items = OrderItem::with(['product', 'order.restaurantTable'])
                    ->whereIn('order_id', $chunk->all())
                    ->orderBy('id')
                    ->get()
                    ->sortBy(fn (OrderItem $i) => [$chunk->search($i->order_id), $i->id]);

                foreach ($items as $i) {
                    $at = Carbon::parse($dates[$i->order_id]);

                    yield [
                        $at, $at,
                        $i->order?->order_number, $i->order?->restaurantTable?->label ?? 'À emporter',
                        $i->product?->name, (int) $i->quantity, (int) $i->unit_price, (int) $i->total,
                        $i->unit_cost !== null ? (int) $i->unit_cost : null,
                        $i->unit_cost !== null ? (int) $i->total - $i->quantity * $i->unit_cost : null,
                        $i->is_offered ? 'Oui' : 'Non', $i->offered_reason,
                    ];
                }
            }
        };

        return ['title' => 'Articles vendus', 'rows' => $rows(), 'columns' => [
            ['Date', 'date'], ['Heure', 'time'], ['Bon', 'text'], ['Table', 'text'], ['Produit', 'text'],
            ['Quantité', 'int'], ['Prix unitaire', 'money'], ['Total facturé', 'money'],
            ['Prix d\'achat unitaire', 'money'], ['Marge', 'money'], ['Offert', 'text'], ['Motif de l\'offert', 'text'],
        ]];
    }

    protected function expenses(Carbon $from, Carbon $to): array
    {
        $rows = function () use ($from, $to) {
            foreach (Expense::with('user')->whereBetween('expense_date', [$from, $to])->orderBy('expense_date')->lazy(500) as $e) {
                yield [
                    $e->expense_date, $e->expense_date,
                    Expense::CATEGORIES[$e->category] ?? $e->category, $e->description,
                    (int) $e->amount, $e->user?->first_name, $e->cash_register_id ? 'Caisse' : 'Hors caisse',
                    self::receiptLabel($e),
                ];
            }
        };

        return ['title' => 'Dépenses', 'rows' => $rows(), 'columns' => [
            ['Date', 'date'], ['Heure', 'time'], ['Catégorie', 'text'], ['Description', 'text'],
            ['Montant', 'money'], ['Saisie par', 'text'], ['Payée par', 'text'], ['Reçu', 'text'],
        ]];
    }

    /** « Oui, prise sur place », « Oui, du téléphone (photo du 12/11/2026) », « À vérifier : photo ancienne… » ou « Non ». */
    public static function receiptLabel(Expense $e): string
    {
        if (! $e->receipt_path) {
            return 'Non';
        }

        $date = $e->receipt_taken_at ? ' (photo du ' . $e->receipt_taken_at->format('d/m/Y') . ')' : '';

        return match (true) {
            $e->receiptLooksOld() => 'À vérifier : photo ancienne' . $date,
            $e->receipt_source === PhotoOrigin::GALLERY => 'Oui, du téléphone' . $date,
            $e->receipt_source === PhotoOrigin::CAMERA => 'Oui, prise sur place',
            default => 'Oui',
        };
    }

    protected function registers(Carbon $from, Carbon $to): array
    {
        $rows = function () use ($from, $to) {
            $registers = CashRegister::with(['openedByUser', 'closedByUser'])
                ->withSum(['movements as added' => fn ($q) => $q->where('type', 'in')], 'amount')
                ->withSum(['movements as removed' => fn ($q) => $q->where('type', 'out')], 'amount')
                ->whereNotNull('closed_at')
                ->whereBetween('closed_at', [$from, $to])
                ->orderBy('closed_at')
                ->lazy(200);

            foreach ($registers as $r) {
                yield [
                    $r->opened_at, $r->closed_at,
                    $r->openedByUser?->first_name, $r->closedByUser?->first_name,
                    (int) $r->opening_amount, (int) $r->added, (int) $r->removed,
                    (int) $r->theoretical_amount, (int) $r->counted_amount,
                    (int) $r->discrepancy, $r->closing_note,
                ];
            }
        };

        return ['title' => 'Clôtures de caisse', 'rows' => $rows(), 'columns' => [
            ['Ouverture', 'datetime'], ['Clôture', 'datetime'], ['Ouverte par', 'text'], ['Clôturée par', 'text'],
            ['Fonds de départ', 'money'], ['Argent ajouté', 'money'], ['Argent retiré', 'money'],
            ['Attendu dans le tiroir', 'money'], ['Compté', 'money'], ['Écart', 'money'], ['Note', 'text'],
        ]];
    }

    protected function drawer(Carbon $from, Carbon $to): array
    {
        $rows = function () use ($from, $to) {
            $moves = CashMovement::with(['user', 'register'])
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->lazy(500);

            foreach ($moves as $m) {
                yield [
                    $m->created_at, $m->created_at,
                    $m->type === 'in' ? 'Ajout' : 'Retrait',
                    $m->type === 'in' ? (int) $m->amount : -(int) $m->amount,
                    $m->reasonLabel(), $m->note, $m->user?->first_name, $m->register?->opened_at,
                ];
            }
        };

        return ['title' => 'Argent du tiroir', 'rows' => $rows(), 'columns' => [
            ['Date', 'date'], ['Heure', 'time'], ['Type', 'text'], ['Montant', 'money'],
            ['Motif', 'text'], ['Note', 'text'], ['Par', 'text'], ['Caisse ouverte le', 'datetime'],
        ]];
    }

    protected function credits(Carbon $from, Carbon $to): array
    {
        $balances = function () {
            $due = CreditTransaction::query()
                ->selectRaw("customer_id, COALESCE(SUM(CASE WHEN type = 'charge' THEN amount ELSE -amount END), 0) as due")
                ->groupBy('customer_id')
                ->pluck('due', 'customer_id');

            foreach (Customer::orderBy('name')->get() as $c) {
                yield [$c->name, $c->phone, (int) ($due[$c->id] ?? 0), $c->credit_limit !== null ? (int) $c->credit_limit : null];
            }
        };

        $moves = function () use ($from, $to) {
            $transactions = CreditTransaction::with(['customer', 'order', 'recordedBy'])
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->lazy(500);

            foreach ($transactions as $t) {
                yield [
                    $t->created_at,
                    $t->type === 'charge' ? 'Consommation' : 'Remboursement',
                    $t->customer?->name, $t->customer?->phone, $t->order?->order_number,
                    $t->recordedBy?->first_name,
                    $t->type === 'charge' ? (int) $t->amount : -(int) $t->amount,
                    $t->note,
                ];
            }
        };

        return [
            ['title' => 'Crédits - soldes', 'rows' => $balances(), 'columns' => [
                ['Client', 'text'], ['Téléphone', 'text'], ['Doit aujourd\'hui', 'money'], ['Plafond', 'money'],
            ]],
            ['title' => 'Crédits - mouvements', 'rows' => $moves(), 'columns' => [
                ['Date', 'datetime'], ['Type', 'text'], ['Client', 'text'], ['Téléphone', 'text'], ['Bon', 'text'],
                ['Par', 'text'], ['Montant', 'money'], ['Note', 'text'],
            ]],
        ];
    }

    protected function stock(Carbon $from, Carbon $to): array
    {
        $state = function () {
            foreach (Product::with('category')->where('track_stock', true)->orderBy('name')->get() as $p) {
                yield [
                    $p->name, $p->category?->name, (int) $p->stock_quantity,
                    $p->cost_price !== null ? (int) $p->cost_price : null,
                    max(0, $p->stock_quantity) * $p->unitValue(),
                ];
            }
        };

        $moves = function () use ($from, $to) {
            $movements = StockMovement::with(['product.category', 'user'])
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->lazy(500);

            foreach ($movements as $m) {
                yield [
                    $m->created_at, $m->typeLabel(), $m->product?->name, $m->product?->category?->name,
                    (int) $m->quantity, $m->unit_cost !== null ? (int) $m->unit_cost : null,
                    $m->unit_cost !== null ? $m->quantity * $m->unit_cost : null,
                    (int) $m->stock_before, (int) $m->stock_after, $m->supplier ?? $m->reason, $m->user?->first_name,
                ];
            }
        };

        return [
            ['title' => 'État du stock', 'rows' => $state(), 'columns' => [
                ['Produit', 'text'], ['Catégorie', 'text'], ['En stock', 'int'], ['Prix d\'achat unitaire', 'money'], ['Valeur', 'money'],
            ]],
            ['title' => 'Stock - mouvements', 'rows' => $moves(), 'columns' => [
                ['Date', 'datetime'], ['Type', 'text'], ['Produit', 'text'], ['Catégorie', 'text'], ['Quantité', 'int'],
                ['Prix d\'achat unitaire', 'money'], ['Valeur', 'money'], ['Stock avant', 'int'], ['Stock après', 'int'],
                ['Fournisseur / motif', 'text'], ['Par', 'text'],
            ]],
        ];
    }

    /**
     * Onglet « Résumé » : les mêmes calculs que le tableau de bord.
     *
     * @return list<array{0: string, 1?: mixed, 2?: string}>
     */
    protected function summary(?\App\Models\Restaurant $restaurant, Carbon $from, Carbon $to): array
    {
        $rid = (int) $restaurant?->id;
        $sales = new SalesSummary($rid, $from, $to);
        $stats = new PeriodStats($rid, $from, $to);

        $expenses = Expense::whereBetween('expense_date', [$from, $to]);
        $expensesTotal = (int) (clone $expenses)->sum('amount');
        $goods = (int) (clone $expenses)->where('category', 'goods_purchase')->sum('amount');
        $other = $expensesTotal - $goods;
        $losses = PeriodStats::stockLosses($rid, $from, $to);
        $gross = $sales->total() - $stats->costOfGoods;
        $profit = $gross - $other - $losses;

        $closings = CashRegister::whereBetween('closed_at', [$from, $to])->get(['id', 'discrepancy']);
        $moves = CashMovement::whereBetween('created_at', [$from, $to]);

        $lines = [
            ['Résumé · ' . ($restaurant?->name ?? 'Maquis'), null, 'title'],
            ['Période', 'du ' . $from->format('d/m/Y') . ' au ' . $to->format('d/m/Y'), 'text'],
            ['Fichier créé le', now()->format('d/m/Y à H:i'), 'text'],
            ['', null, 'blank'],
            ['VENTES', null, 'bold'],
            ['Chiffre d\'affaires (crédit compris)', $sales->total(), 'bold'],
            ['   dont espèces', $sales->cash],
        ];
        foreach ($sales->mobile as $method => $amount) {
            $lines[] = ['   dont ' . (Payment::METHODS[$method] ?? $method), (int) $amount];
        }
        array_push($lines,
            ['   dont à crédit', $sales->credit],
            ['Bons réglés', $sales->count(), 'int'],
            ['Gestes commerciaux (offerts et remises)', $stats->gesturesTotal],
            ['Bons annulés', $stats->cancelledCount, 'int'],
            ['', null, 'blank'],
            ['BÉNÉFICE ESTIMÉ', null, 'bold'],
            ['Chiffre d\'affaires', $sales->total()],
            ['− prix d\'achat des produits vendus', $stats->costOfGoods],
            ['− dépenses (hors achats de marchandises)', $other],
            ['− pertes de stock (casse, écarts de comptage)', $losses],
            ['= Bénéfice estimé', $profit, 'bold'],
        );
        if ($stats->uncostedQuantity > 0) {
            $lines[] = ["Attention : {$stats->uncostedQuantity} article(s) vendu(s) sans prix d'achat, le bénéfice est surestimé.", null, 'note'];
        }
        array_push($lines,
            ['', null, 'blank'],
            ['DÉPENSES', null, 'bold'],
            ['Total des dépenses', $expensesTotal],
            ['   dont achats de marchandises', $goods],
            ['   dont autres dépenses', $other],
            ['', null, 'blank'],
            ['CAISSE', null, 'bold'],
            ['Clôtures de caisse', $closings->count(), 'int'],
            ['Clôtures avec un écart', $closings->filter(fn ($c) => abs((int) $c->discrepancy) >= CashRegisterManage::DISCREPANCY_THRESHOLD)->count(), 'int'],
            ['Total des écarts (négatif = manque)', (int) $closings->sum('discrepancy')],
            ['Argent ajouté dans le tiroir', (int) (clone $moves)->where('type', 'in')->sum('amount')],
            ['Argent retiré du tiroir', (int) (clone $moves)->where('type', 'out')->sum('amount')],
            ['', null, 'blank'],
            ['AUJOURD\'HUI', null, 'bold'],
            ['Crédits que les clients doivent encore', PeriodStats::creditDue($rid)],
            ['Argent encore chez les serveuses (sacoches)', PeriodStats::sacochesOutstanding($rid)],
            ['', null, 'blank'],
            ['Mêmes calculs que le tableau de bord de Maquis Express. Le détail est dans les autres onglets.', null, 'note'],
        );

        return $lines;
    }
}
