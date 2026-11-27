<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Support\PeriodStats;
use App\Support\SalesSummary;
use Carbon\CarbonInterface;
use Livewire\Component;

/**
 * Tableau de bord du patron et de la gérante.
 *
 * - En ce moment : caisse, comptoir, tables, crédits, sacoches.
 * - Sur la période (aujourd'hui, semaine, mois) : chiffre d'affaires comparé
 *   à la même période précédente, comment il a été payé, qui a vendu, ce qui
 *   s'est vendu, ce qu'il faut surveiller (offerts, annulations, écarts).
 */
class Dashboard extends Component
{
    use \App\Livewire\Concerns\ShowsBusinessErrors;

    public string $period = 'today'; // today | week | month

    public const PERIODS = [
        'today' => ["Aujourd'hui", 'hier à la même heure'],
        'week' => ['Cette semaine', 'la semaine dernière au même moment'],
        'month' => ['Ce mois', 'le mois dernier au même moment'],
    ];

    public function setPeriod(string $period)
    {
        $this->period = array_key_exists($period, self::PERIODS) ? $period : 'today';
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    protected function periodRange(): array
    {
        return match ($this->period) {
            'week' => [now()->startOfWeek(), now()],
            'month' => [now()->startOfMonth(), now()],
            default => [now()->startOfDay(), now()],
        };
    }

    /** Même durée écoulée, sur la période précédente (comparaison équitable). */
    protected function previousRange(): array
    {
        [$start, $end] = $this->periodRange();

        return match ($this->period) {
            'week' => [$start->copy()->subWeek(), $end->copy()->subWeek()],
            'month' => [$start->copy()->subMonthNoOverflow(), $end->copy()->subMonthNoOverflow()],
            default => [$start->copy()->subDay(), $end->copy()->subDay()],
        };
    }

    /**
     * « Pour bien démarrer » (nouveau maquis), dans l'ordre qui fait vendre vite :
     * le menu, la première vente, puis les tables et l'équipe.
     * Quand tout est fait : un « Bravo » une seule fois, puis le guide disparaît pour toujours.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: bool} [étapes, afficher le « Bravo »]
     */
    protected function setupGuide(?\App\Models\Restaurant $restaurant, bool $hasProducts): array
    {
        $user = auth()->user();
        $canSetup = in_array($user->role, ['owner', 'manager'], true) || $user->isPlatformAdmin();

        if (! $restaurant || ! $canSetup || $restaurant->setup_done_at) {
            return [[], false];
        }

        $registerOpen = CashRegister::where('restaurant_id', $restaurant->id)->whereNull('closed_at')->exists();
        $saleUrl = $restaurant->usesQuickSale() ? route('orders.new') : route('tables.index');
        $hasTables = RestaurantTable::where('restaurant_id', $restaurant->id)->exists();

        $menu = ['key' => 'menu', 'done' => $hasProducts, 'title' => 'Créez votre menu', 'text' => 'Vos boissons et vos plats, avec leurs prix.', 'url' => route('products.manage'), 'cta' => 'Créer le menu'];
        $sale = ['key' => 'sale', 'done' => Order::where('status', 'paid')->exists(), 'title' => 'Ouvrez la caisse et vendez',
            'text' => $registerOpen ? 'La caisse est ouverte : encaissez votre première vente.' : 'Ouvrez la caisse, puis faites votre première vente.',
            'url' => $registerOpen ? $saleUrl : route('cash-register.manage', ['puis' => 'vendre']),
            'cta' => $registerOpen ? 'Vendre maintenant' : 'Ouvrir la caisse'];
        $tables = $restaurant->usesTables()
            ? ['key' => 'tables', 'done' => $hasTables, 'title' => 'Créez vos tables', 'text' => 'Table 1, Table 2… en un clic.', 'url' => route('tables.manage'), 'cta' => 'Créer les tables']
            : null;
        $team = ['key' => 'team', 'done' => \App\Models\User::where('restaurant_id', $restaurant->id)->where('role', '!=', 'owner')->whereNull('left_at')->exists(),
            'title' => 'Ajoutez votre équipe', 'text' => 'Serveuses, caissier, gérante : chacun son compte.', 'url' => route('users.manage'), 'cta' => 'Ajouter quelqu\'un'];

        // Service à table seulement : sans tables, pas de vente possible, donc les tables avant
        $steps = $restaurant->service_mode === 'tables' && ! $hasTables
            ? [$menu, $tables, $sale, $team]
            : array_values(array_filter([$menu, $sale, $tables, $team]));

        if (collect($steps)->contains('done', false)) {
            return [$steps, false];
        }

        $restaurant->forceFill(['setup_done_at' => now()])->save();

        return [[], true];
    }

    /** « Je n'ai pas de tables » : seul le patron change le fonctionnement du maquis. */
    protected function canChangeServiceMode(): bool
    {
        $user = auth()->user();

        return $user->role === 'owner' || $user->isPlatformAdmin();
    }

    /** Depuis le guide : « Je n'ai pas de tables » passe le maquis en vente rapide. */
    public function skipTables(): void
    {
        $restaurant = auth()->user()->currentRestaurant();

        if (! $restaurant || ! $this->canChangeServiceMode() || ! $restaurant->usesTables()) {
            return;
        }

        if (RestaurantTable::where('restaurant_id', $restaurant->id)->exists()) {
            \App\Support\Toast::error('Vous avez déjà des tables. Pour les retirer, passez par les Paramètres.');

            return;
        }

        $restaurant->update(['service_mode' => 'quick']);
        \App\Models\ActivityLog::record('settings_changed', 'a choisi la vente rapide, sans tables.');
        \App\Support\Toast::success('C\'est noté : vente rapide, sans tables. Vous pourrez ajouter des tables plus tard dans les Paramètres.');
    }

    public function render()
    {
        $restaurantId = auth()->user()->currentRestaurantId();

        if (! $restaurantId) {
            return view('livewire.dashboard', ['noRestaurant' => true]);
        }

        $restaurant = auth()->user()->currentRestaurant();
        [$start, $end] = $this->periodRange();
        [$prevStart, $prevEnd] = $this->previousRange();

        $sales = new SalesSummary($restaurantId, $start, $end);
        $previous = new SalesSummary($restaurantId, $prevStart, $prevEnd);
        $stats = new PeriodStats($restaurantId, $start, $end, topLimit: 5);

        $expenses = (int) Expense::where('restaurant_id', $restaurantId)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        $expensesByCategory = Expense::where('restaurant_id', $restaurantId)
            ->whereBetween('expense_date', [$start, $end])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Bénéfice : ventes − coût des produits vendus − dépenses (sauf achats de
        // marchandises, déjà comptés dans le coût des produits) − pertes de stock
        $otherExpenses = (int) Expense::where('restaurant_id', $restaurantId)
            ->whereBetween('expense_date', [$start, $end])
            ->where('category', '!=', 'goods_purchase')
            ->sum('amount');
        $stockLosses = PeriodStats::stockLosses($restaurantId, $start, $end);
        $grossMargin = $sales->total() - $stats->costOfGoods;
        $profit = $grossMargin - $otherExpenses - $stockLosses;

        // Écarts de caisse des clôtures de la période
        $closings = CashRegister::where('restaurant_id', $restaurantId)
            ->whereBetween('closed_at', [$start, $end])
            ->get(['id', 'discrepancy']);

        // En ce moment
        $register = CashRegister::with('openedByUser:id,first_name')
            ->where('restaurant_id', $restaurantId)
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        $pending = Order::where('status', 'awaiting_payment');

        // 7 derniers jours, tout compris (deux demandes à la base pour les 7 jours)
        $byDay = SalesSummary::totalsByDay($restaurantId, now()->subDays(6)->startOfDay(), now()->endOfDay());
        $chartData = collect(range(6, 0))->map(function (int $daysAgo) use ($byDay) {
            $day = now()->subDays($daysAgo);

            return [
                'label' => $daysAgo === 0 ? "Auj." : ucfirst($day->locale('fr')->isoFormat('ddd D')),
                'value' => $byDay[$day->toDateString()] ?? 0,
            ];
        })->all();

        $hasProducts = Product::exists();
        [$setup, $celebrate] = $this->setupGuide($restaurant, $hasProducts);

        return view('livewire.dashboard', [
            'noRestaurant' => false,
            'setup' => $setup,
            'celebrate' => $celebrate,
            'canSkipTables' => $this->canChangeServiceMode(),
            'stockCountDays' => (new \App\Support\StockCountSheet($restaurantId))->daysSince(),
            'hasProducts' => $hasProducts,
            'periods' => self::PERIODS,
            'sales' => $sales,
            'revenue' => $sales->total(),
            'orderCount' => $sales->count(),
            'averageTicket' => $sales->count() > 0 ? intdiv($sales->total(), $sales->count()) : 0,
            'previousRevenue' => $previous->total(),
            'expenses' => $expenses,
            'expensesByCategory' => $expensesByCategory,
            'netCash' => $sales->collected() - $expenses,
            'costOfGoods' => $stats->costOfGoods,
            'grossMargin' => $grossMargin,
            'otherExpenses' => $otherExpenses,
            'stockLosses' => $stockLosses,
            'profit' => $profit,
            'uncostedQuantity' => $stats->uncostedQuantity,
            'stats' => $stats,
            'closingsCount' => $closings->count(),
            'closingsWithGap' => $closings->filter(fn ($c) => abs((int) $c->discrepancy) >= CashRegisterManage::DISCREPANCY_THRESHOLD)->count(),
            'closingsGapTotal' => (int) $closings->sum('discrepancy'),
            'register' => $register,
            'drawerAmount' => $register?->calculateTheoreticalAmount(),
            'paysBefore' => (bool) $restaurant?->paysBeforeService(),
            'showCounter' => (bool) $restaurant?->paysBeforeService() && ($restaurant->hasActiveServers() || Order::where('status', 'awaiting_payment')->exists()),
            'pendingCount' => (clone $pending)->count(),
            'pendingTotal' => (int) (clone $pending)->sum('total'),
            'occupiedTables' => RestaurantTable::where('restaurant_id', $restaurantId)->where('status', '!=', 'free')->count(),
            'totalTables' => $restaurant?->usesTables() ? RestaurantTable::where('restaurant_id', $restaurantId)->count() : 0,
            'creditEnabled' => (bool) $restaurant?->creditEnabled(),
            'creditDue' => PeriodStats::creditDue($restaurantId),
            'sacoches' => PeriodStats::sacochesOutstanding($restaurantId),
            'chartData' => $chartData,
            'lowStockProducts' => Product::where('restaurant_id', $restaurantId)->active()->where('track_stock', true)
                ->whereNotNull('stock_alert_threshold')
                ->whereColumn('stock_quantity', '<=', 'stock_alert_threshold')
                ->orderBy('stock_quantity')
                ->get(),
        ]);
    }
}
