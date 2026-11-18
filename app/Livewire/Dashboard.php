<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Support\SalesSummary;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\CashRegister;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Tableau de bord (PRD section 7). KPIs essentiels uniquement au MVP :
 * CA, nombre de commandes, dépenses, bénéfice estimé, tables occupées.
 */
class Dashboard extends Component
{
    public string $period = 'today'; // today | week | month

    public function setPeriod(string $period)
    {
        $this->period = $period;
    }

    protected function periodRange(): array
    {
        return match ($this->period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * Chiffre d'affaires = tout ce qui a été réglé : espèces, Mobile Money et ardoise.
     */
    protected function revenueAndOrderCount($restaurantId, $start, $end): array
    {
        $sales = new SalesSummary($restaurantId, $start, $end);

        return [$sales->total(), $sales->count(), $sales];
    }

    public function render()
    {
        $restaurantId = auth()->user()->currentRestaurantId();

        if (!$restaurantId) {
            return view('livewire.dashboard', [
                'noRestaurant' => true,
                'revenue' => 0,
                'orderCount' => 0,
                'expenses' => 0,
                'profit' => 0,
                'occupiedTables' => 0,
                'totalTables' => 0,
                'chartData' => [],
                'lowStockProducts' => collect(),
                'latestRegister' => null,
            ]);
        }

        [$start, $end] = $this->periodRange();

        [$revenue, $orderCount, $sales] = $this->revenueAndOrderCount(
            $restaurantId,
            $start,
            $end
        );

        $expenses = Expense::where('restaurant_id', $restaurantId)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        $profit = $revenue - $expenses;

        $totalTables = RestaurantTable::where('restaurant_id', $restaurantId)
            ->count();

        $occupiedTables = RestaurantTable::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['occupied', 'awaiting_payment'])
            ->count();

        // Graphique simple : CA des 7 derniers jours
        $chartData = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->copy()->subDays($i);

            [$dayRevenue] = $this->revenueAndOrderCount(
                $restaurantId,
                $day->copy()->startOfDay(),
                $day->copy()->endOfDay()
            );

            $chartData[] = [
                'label' => $day->format('d/m'),
                'value' => $dayRevenue,
            ];
        }

        // Dernière caisse
        $latestRegister = CashRegister::where('restaurant_id', $restaurantId)
            ->latest('opened_at')
            ->first();

        // Produits dont le stock est inférieur ou égal au seuil d'alerte
        $lowStockProducts = Product::where('restaurant_id', $restaurantId)
            ->whereNotNull('stock_alert_threshold')
            ->whereColumn(
                'stock_quantity',
                '<=',
                'stock_alert_threshold'
            )
            ->orderBy('stock_quantity')
            ->get();

        return view('livewire.dashboard', [
            'noRestaurant' => false,
            'revenue' => $revenue,
            'sales' => $sales,
            'orderCount' => $orderCount,
            'expenses' => $expenses,
            'profit' => $profit,
            'occupiedTables' => $occupiedTables,
            'totalTables' => $totalTables,
            'chartData' => $chartData,

            // IMPORTANT : maintenant disponible dans dashboard.blade.php
            'lowStockProducts' => $lowStockProducts,

            // Disponible également si utilisé dans la vue
            'latestRegister' => $latestRegister,
        ]);
    }
}
