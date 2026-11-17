<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\Expense;
use Livewire\Component;

/**
 * Saisie des dépenses (PRD section 16). Rattachée à la session de caisse
 * ouverte quand il y en a une, pour que le calcul de solde théorique
 * (CashRegister::calculateTheoreticalAmount) les prenne en compte.
 */
class ExpensesList extends Component
{
    public string $category = 'goods_purchase';
    public int $amount = 0;
    public string $description = '';

    public array $categories = [
        'goods_purchase' => 'Achat de marchandises',
        'salary' => 'Salaires',
        'electricity' => 'Électricité',
        'water' => 'Eau',
        'internet' => 'Internet',
        'transport' => 'Transport',
        'maintenance' => 'Entretien',
        'repair' => 'Réparation',
        'rent' => 'Loyer',
        'other' => 'Autres',
    ];

    public function addExpense()
    {
        $this->validate([
            'category' => 'required|string',
            'amount' => 'required|integer|min:1',
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

        $this->amount = 0;
        $this->description = '';

        session()->flash('success', 'Dépense enregistrée.');
    }

    public function render()
    {
        $expenses = Expense::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereDate('expense_date', now()->toDateString())
            ->latest()
            ->get();

        return view('livewire.expenses-list', [
            'expenses' => $expenses,
            'total' => $expenses->sum('amount'),
        ]);
    }
}