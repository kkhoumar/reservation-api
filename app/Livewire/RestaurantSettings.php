<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Les 2 réglages du maquis, modifiables par le propriétaire
 * (et le Super Admin) : mode de service et ardoise.
 */
class RestaurantSettings extends Component
{
    public string $serviceMode = 'both';
    public bool $creditEnabled = false;
    public string $paymentTiming = 'before';

    public function mount(): void
    {
        $restaurant = $this->restaurant();

        $this->serviceMode = $restaurant->service_mode;
        $this->creditEnabled = $restaurant->creditEnabled();
        $this->paymentTiming = $restaurant->payment_timing;
    }

    private function restaurant(): Restaurant
    {
        return auth()->user()->currentRestaurant() ?? abort(403);
    }

    public function save()
    {
        $this->validate([
            'serviceMode' => ['required', Rule::in(array_keys(Restaurant::SERVICE_MODES))],
            'creditEnabled' => 'boolean',
            'paymentTiming' => ['required', Rule::in(array_keys(Restaurant::PAYMENT_TIMINGS))],
        ]);

        $restaurant = $this->restaurant();

        // Désactiver l'ardoise cacherait des dettes encore dues
        if (! $this->creditEnabled && $restaurant->creditEnabled()) {
            $due = (int) \App\Models\CreditTransaction::query()
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'charge' THEN amount ELSE -amount END), 0) as due")
                ->value('due');

            if ($due > 0) {
                $this->addError('creditEnabled', 'Impossible pour l\'instant : des clients doivent encore '
                    . number_format($due, 0, ',', ' ') . ' FCFA sur l\'ardoise.');

                return;
            }
        }

        // On ne change pas de fonctionnement avec des bons en attente au comptoir
        if ($this->paymentTiming !== $restaurant->payment_timing) {
            $pending = Order::where('status', 'awaiting_payment')->count();

            if ($pending > 0) {
                $this->addError('paymentTiming', "Impossible pour l'instant : {$pending} commande(s) attendent d'être encaissées. Encaisse-les d'abord.");

                return;
            }
        }

        // On ne retire pas les tables tant que des clients y sont encore servis
        if ($this->serviceMode === 'quick' && $restaurant->usesTables()) {
            $openTableOrders = Order::whereNotNull('table_id')
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->count();

            if ($openTableOrders > 0) {
                $this->addError('serviceMode', "Impossible pour l'instant : {$openTableOrders} commande(s) de table ne sont pas encore payées. Encaisse-les d'abord.");

                return;
            }
        }

        $restaurant->update([
            'service_mode' => $this->serviceMode,
            'credit_enabled' => $this->creditEnabled,
            'payment_timing' => $this->paymentTiming,
        ]);

        Log::info('Réglages du maquis modifiés', [
            'user_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
            'service_mode' => $this->serviceMode,
            'credit_enabled' => $this->creditEnabled,
            'payment_timing' => $this->paymentTiming,
        ]);

        session()->flash('success', 'Réglages enregistrés.');
    }

    public function render()
    {
        return view('livewire.restaurant-settings', [
            'modes' => Restaurant::SERVICE_MODES,
            'timings' => Restaurant::PAYMENT_TIMINGS,
        ]);
    }
}
