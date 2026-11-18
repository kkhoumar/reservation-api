<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Dépenses : saisie (rattachée à la caisse ouverte, pour que le solde
 * théorique en tienne compte) et historique filtrable.
 *
 * - Caissière : saisit et voit les dépenses du jour.
 * - Patron et gérante : en plus, tout l'historique (période, catégorie).
 */
class ExpensesList extends Component
{
    use WithPagination;

    public string $category = 'goods_purchase';
    public ?int $amount = null;
    public string $description = '';

    // Filtres de l'historique
    public string $period = 'today'; // today | 7days | month | last_month | custom
    public string $filterCategory = '';
    public string $from = '';
    public string $to = '';

    #[Locked]
    public array $categories = Expense::CATEGORIES;

    public const PERIODS = [
        'today' => "Aujourd'hui",
        '7days' => '7 derniers jours',
        'month' => 'Ce mois',
        'last_month' => 'Mois dernier',
        'custom' => 'Dates choisies',
    ];

    protected function canSeeHistory(): bool
    {
        $user = auth()->user();

        return $user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager'], true);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['period', 'filterCategory', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function addExpense()
    {
        $this->validate([
            'category' => ['required', 'string', Rule::in(array_keys($this->categories))],
            'amount' => 'required|integer|min:1|max:100000000',
            'description' => 'nullable|string|max:255',
        ]);

        $openRegister = CashRegister::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        Expense::create([
            'restaurant_id' => auth()->user()->currentRestaurantId(),
            'user_id' => auth()->id(),
            'cash_register_id' => $openRegister?->id,
            'category' => $this->category,
            'amount' => $this->amount,
            'description' => $this->description ?: null,
            'expense_date' => now(),
        ]);

        $this->amount = null;
        $this->description = '';

        session()->flash('success', 'Dépense enregistrée.');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function range(): array
    {
        $period = $this->canSeeHistory() ? $this->period : 'today';

        return match ($period) {
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'custom' => $this->customRange(),
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function customRange(): array
    {
        try {
            $from = $this->from !== '' ? Carbon::parse($this->from)->startOfDay() : now()->startOfMonth();
            $to = $this->to !== '' ? Carbon::parse($this->to)->endOfDay() : now()->endOfDay();
        } catch (\Throwable) {
            return [now()->startOfDay(), now()->endOfDay()];
        }

        return $from->lte($to) ? [$from, $to] : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
    }

    public function render()
    {
        [$start, $end] = $this->range();
        $history = $this->canSeeHistory();

        $query = Expense::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereBetween('expense_date', [$start, $end])
            ->when($history && array_key_exists($this->filterCategory, $this->categories),
                fn ($q) => $q->where('category', $this->filterCategory));

        $byCategory = (clone $query)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as n')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $expenses = (clone $query)
            ->with('user:id,first_name')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(30);

        return view('livewire.expenses-list', [
            'expenses' => $expenses,
            'total' => (int) $byCategory->sum('total'),
            'count' => (int) $byCategory->sum('n'),
            'byCategory' => $byCategory,
            'canSeeHistory' => $history,
            'periods' => self::PERIODS,
            'rangeLabel' => $start->isSameDay($end)
                ? ($start->isToday() ? "Aujourd'hui" : $start->format('d/m/Y'))
                : 'Du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y'),
        ]);
    }
}
