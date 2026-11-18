<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * L'ardoise : qui doit combien, remboursements en espèces et relance WhatsApp.
 *
 * - Patron et gérante : ajoutent les clients et fixent les plafonds.
 * - Patron, gérante et caissière : encaissent les remboursements.
 */
class Ardoise extends Component
{
    public string $search = '';

    #[Locked]
    public ?int $customerId = null;

    // Remboursement
    public ?int $repaymentAmount = null;
    public string $repaymentNote = '';

    // Nouveau client / plafond
    public bool $showCreate = false;
    public string $name = '';
    public string $phone = '';
    public ?int $creditLimit = null;
    public ?int $editLimit = null;

    public function mount(): void
    {
        if (! auth()->user()->currentRestaurant()?->creditEnabled()) {
            session()->flash('error', "Le crédit client n'est pas activé. Le patron peut l'activer dans Paramètres.");
            $this->redirect(route('tables.index'), navigate: false);
        }
    }

    protected function canManage(): bool
    {
        return CreditTransaction::canAuthorizeCredit(auth()->user());
    }

    public function select(int $id): void
    {
        $customer = Customer::findOrFail($id);

        $this->customerId = $customer->id;
        $this->repaymentAmount = null;
        $this->repaymentNote = '';
        $this->editLimit = $customer->credit_limit;
        $this->showCreate = false;
        $this->resetValidation();
    }

    public function closeCustomer(): void
    {
        $this->reset(['customerId', 'repaymentAmount', 'repaymentNote', 'editLimit']);
        $this->resetValidation();
    }

    public function createCustomer(): void
    {
        abort_unless($this->canManage(), 403);

        $this->phone = $this->phone !== '' ? User::normalizePhone($this->phone) : '';

        $this->validate([
            'name' => 'required|string|max:100',
            'phone' => ['nullable', 'regex:' . User::PHONE_REGEX],
            'creditLimit' => 'nullable|integer|min:0|max:100000000',
        ], [
            'phone.regex' => 'Numéro invalide : 10 chiffres, par exemple 0707070707.',
        ]);

        if ($this->phone !== '' && Customer::where('phone', $this->phone)->exists()) {
            $this->addError('phone', 'Un client avec ce numéro existe déjà.');

            return;
        }

        $customer = Customer::create([
            'restaurant_id' => auth()->user()->currentRestaurantId(),
            'name' => trim($this->name),
            'phone' => $this->phone ?: null,
            'credit_limit' => $this->creditLimit,
        ]);

        $this->reset(['name', 'phone', 'creditLimit', 'showCreate']);
        session()->flash('success', "Client {$customer->name} ajouté.");
    }

    public function saveLimit(): void
    {
        abort_unless($this->canManage(), 403);

        if (! $this->customerId) {
            return;
        }

        $this->validate(['editLimit' => 'nullable|integer|min:0|max:100000000']);

        Customer::findOrFail($this->customerId)->update(['credit_limit' => $this->editLimit]);
        session()->flash('success', 'Plafond enregistré.');
    }

    public function recordRepayment(): void
    {
        if (! $this->customerId) {
            return;
        }

        $this->validate([
            'repaymentAmount' => 'required|integer|min:1|max:100000000',
            'repaymentNote' => 'nullable|string|max:255',
        ]);

        $customer = Customer::findOrFail($this->customerId);

        try {
            CreditTransaction::recordRepayment($customer, auth()->user(), $this->repaymentAmount, trim($this->repaymentNote));
        } catch (BusinessRuleException $e) {
            $this->addError('repaymentAmount', $e->getMessage());

            return;
        }

        session()->flash('success', number_format($this->repaymentAmount, 0, ',', ' ') . " FCFA remboursés par {$customer->name} : l'argent est en caisse.");
        $this->repaymentAmount = null;
        $this->repaymentNote = '';
    }

    public function render()
    {
        // Encours calculé en une seule requête pour toute la liste
        $balances = CreditTransaction::query()
            ->select('customer_id', DB::raw("SUM(CASE WHEN type = 'charge' THEN amount ELSE -amount END) as balance"), DB::raw('MAX(created_at) as last_at'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $customers = Customer::query()
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . mb_strtolower(trim($this->search)) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$term])->orWhere('phone', 'like', $term));
            })
            ->get()
            ->each(function (Customer $c) use ($balances) {
                $c->current_balance = (int) ($balances[$c->id]->balance ?? 0);
                $c->last_at = $balances[$c->id]->last_at ?? null;
            })
            ->sortByDesc('current_balance')
            ->values();

        $selected = $this->customerId ? Customer::find($this->customerId) : null;
        $restaurantName = auth()->user()->currentRestaurant()?->name ?? '';

        return view('livewire.ardoise', [
            'customers' => $customers,
            'totalDue' => $balances->sum(fn ($b) => max(0, (int) $b->balance)),
            'selected' => $selected,
            'selectedBalance' => $selected?->balance() ?? 0,
            'history' => $selected
                ? $selected->transactions()
                    ->with(['order.items.product', 'order.server', 'order.restaurantTable', 'recordedBy'])
                    ->latest()
                    ->limit(30)
                    ->get()
                : collect(),
            'whatsappUrl' => $selected?->whatsappUrl($restaurantName),
            'canManage' => $this->canManage(),
        ]);
    }
}
