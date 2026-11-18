<?php

namespace App\Livewire\Admin;

use App\Actions\RecordSubscriptionPayment;
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

        session()->flash('success', 'Paiement de ' . number_format($payment->amount, 0, ',', ' ')
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

        session()->flash('success', 'Formule enregistrée.');
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
        ]);
    }
}
