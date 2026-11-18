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

        // 7 derniers jours (une requête par jour, tout compris)
        $chartData = collect(range(6, 0))->map(function (int $daysAgo) use ($restaurantId) {
            $day = now()->subDays($daysAgo);

            return [
                'label' => $daysAgo === 0 ? "Auj." : ucfirst($day->locale('fr')->isoFormat('ddd D')),
                'value' => (new SalesSummary($restaurantId, $day->copy()->startOfDay(), $day->copy()->endOfDay()))->total(),
            ];
        })->all();

        return view('livewire.dashboard', [
            'noRestaurant' => false,
            'periods' => self::PERIODS,
            'sales' => $sales,
            'revenue' => $sales->total(),
            'orderCount' => $sales->count(),
            'averageTicket' => $sales->count() > 0 ? intdiv($sales->total(), $sales->count()) : 0,
            'previousRevenue' => $previous->total(),
            'expenses' => $expenses,
            'expensesByCategory' => $expensesByCategory,
            'netCash' => $sales->collected() - $expenses,
            'stats' => $stats,
            'closingsCount' => $closings->count(),
            'closingsWithGap' => $closings->filter(fn ($c) => abs((int) $c->discrepancy) >= CashRegisterManage::DISCREPANCY_THRESHOLD)->count(),
            'closingsGapTotal' => (int) $closings->sum('discrepancy'),
            'register' => $register,
            'drawerAmount' => $register?->calculateTheoreticalAmount(),
            'paysBefore' => (bool) $restaurant?->paysBeforeService(),
            'pendingCount' => (clone $pending)->count(),
            'pendingTotal' => (int) (clone $pending)->sum('total'),
            'occupiedTables' => RestaurantTable::where('restaurant_id', $restaurantId)->where('status', '!=', 'free')->count(),
            'totalTables' => $restaurant?->usesTables() ? RestaurantTable::where('restaurant_id', $restaurantId)->count() : 0,
            'creditEnabled' => (bool) $restaurant?->creditEnabled(),
            'creditDue' => PeriodStats::creditDue($restaurantId),
            'sacoches' => PeriodStats::sacochesOutstanding($restaurantId),
            'chartData' => $chartData,
            'lowStockProducts' => Product::where('restaurant_id', $restaurantId)
                ->whereNotNull('stock_alert_threshold')
                ->whereColumn('stock_quantity', '<=', 'stock_alert_threshold')
                ->orderBy('stock_quantity')
                ->get(),
        ]);
    }
}
