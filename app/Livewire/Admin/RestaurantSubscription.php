<?php

namespace App\Livewire\Admin;

use App\Actions\RecordSubscriptionPayment;
use App\Actions\RestaurantDataManager;
use App\Exceptions\BusinessRuleException;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Super Admin : l'abonnement d'un maquis (formule, paiements, échéance).
 */
class RestaurantSubscription extends Component
{
    #[Locked]
    public int $restaurantId;

    public ?int $planId = null;
    public int $months = 1;
    public string $method = 'wave';
    public ?int $amount = null;
    public string $reference = '';
    public string $note = '';

    public function mount(Restaurant $restaurant): void
    {
        $this->restaurantId = $restaurant->id;
        $this->planId = $restaurant->plan_id ?? Plan::where('is_active', true)->orderBy('sort_order')->value('id');
        $this->fillAmount();
    }

    protected function restaurant(): Restaurant
    {
        return Restaurant::with('plan')->findOrFail($this->restaurantId);
    }

    /** Montant proposé = prix de la formule × nombre de mois (modifiable). */
    protected function fillAmount(): void
    {
        $price = Plan::find($this->planId)?->monthly_price;
        $this->amount = $price !== null ? $price * $this->months : null;
    }

    public function updatedPlanId(): void
    {
        $this->fillAmount();
    }

    public function updatedMonths(): void
    {
        $this->fillAmount();
    }

    public function recordPayment(RecordSubscriptionPayment $action): void
    {
        $this->validate([
            'planId' => ['required', Rule::exists('plans', 'id')->where('is_active', true)],
            'months' => ['required', 'integer', Rule::in(RecordSubscriptionPayment::ALLOWED_MONTHS)],
            'method' => ['required', Rule::in(array_keys(SubscriptionPayment::METHODS))],
            'amount' => 'required|integer|min:0|max:100000000',
            'reference' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $payment = $action->handle(
                $this->restaurant(),
                Plan::findOrFail($this->planId),
                $this->months,
                $this->method,
                auth()->user(),
                $this->amount,
                trim($this->reference) ?: null,
                trim($this->note) ?: null,
            );
        } catch (BusinessRuleException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->reference = '';
        $this->note = '';

        \App\Support\Toast::success('Paiement de ' . number_format($payment->amount, 0, ',', ' ')
            . ' FCFA enregistré : accès payé jusqu\'au ' . $payment->period_end->format('d/m/Y') . '.');
    }

    /** Changer de formule sans paiement (par exemple pendant l'essai). */
    public function changePlan(): void
    {
        $this->validate(['planId' => ['required', Rule::exists('plans', 'id')->where('is_active', true)]]);

        $restaurant = $this->restaurant();
        $restaurant->forceFill(['plan_id' => $this->planId])->save();

        Log::info('Abonnement : formule modifiée', [
            'admin_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
            'plan_id' => $this->planId,
        ]);

        \App\Support\Toast::success('Formule enregistrée.');
    }

    // ---------- Zone dangereuse ----------

    public string $resetConfirm = '';
    public bool $resetCustomers = false;
    public bool $resetStock = false;
    public string $deleteConfirm = '';

    /** Il faut retaper le nom exact du maquis (sans tenir compte des majuscules ni des espaces autour). */
    protected function confirms(string $typed): bool
    {
        return mb_strtolower(trim($typed)) === mb_strtolower(trim($this->restaurant()->name));
    }

    public function resetData(RestaurantDataManager $manager): void
    {
        if (! $this->confirms($this->resetConfirm)) {
            $this->addError('resetConfirm', 'Tapez exactement le nom du maquis pour confirmer.');

            return;
        }

        $counts = $manager->resetTestData($this->restaurant(), auth()->user(), $this->resetCustomers, $this->resetStock);

        $this->reset(['resetConfirm', 'resetCustomers', 'resetStock']);
        $total = array_sum($counts);
        \App\Support\Toast::success("Données de test vidées ({$total} lignes effacées : "
            . collect($counts)->filter()->map(fn ($n, $k) => "{$n} {$k}")->join(', ')
            . "). Le menu, les tables, l'équipe et les réglages sont conservés.");
    }

    public function deleteRestaurant(RestaurantDataManager $manager)
    {
        $restaurant = $this->restaurant();

        if (! $this->confirms($this->deleteConfirm)) {
            $this->addError('deleteConfirm', 'Tapez exactement le nom du maquis pour confirmer.');

            return;
        }

        $manager->deleteRestaurant($restaurant, auth()->user());

        // Le Super Admin était peut-être « entré » dans ce maquis
        if ((int) session(\App\Models\User::ADMIN_RESTAURANT_SESSION_KEY) === $restaurant->id) {
            session()->forget(\App\Models\User::ADMIN_RESTAURANT_SESSION_KEY);
        }

        \App\Support\Toast::success("Le maquis « {$restaurant->name} » et toutes ses données ont été supprimés.");

        return $this->redirect(route('admin.restaurants'), navigate: false);
    }

    public function render()
    {
        $restaurant = $this->restaurant();

        return view('livewire.admin.restaurant-subscription', [
            'restaurant' => $restaurant,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'payments' => $restaurant->subscriptionPayments()->with(['plan', 'recordedBy'])->latest()->get(),
            'methods' => SubscriptionPayment::METHODS,
            'allowedMonths' => RecordSubscriptionPayment::ALLOWED_MONTHS,
            'activeUsers' => $restaurant->activeUsersCount(),
            'activity' => [
                'orders' => \Illuminate\Support\Facades\DB::table('orders')->where('restaurant_id', $restaurant->id)->count(),
                'registers' => \Illuminate\Support\Facades\DB::table('cash_registers')->where('restaurant_id', $restaurant->id)->count(),
                'expenses' => \Illuminate\Support\Facades\DB::table('expenses')->where('restaurant_id', $restaurant->id)->count(),
                'customers' => \Illuminate\Support\Facades\DB::table('customers')->where('restaurant_id', $restaurant->id)->count(),
                'users' => \Illuminate\Support\Facades\DB::table('users')->where('restaurant_id', $restaurant->id)->count(),
                'paid' => (int) \Illuminate\Support\Facades\DB::table('subscription_payments')->where('restaurant_id', $restaurant->id)->sum('amount'),
            ],
        ]);
    }
}
