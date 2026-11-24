<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\WhatsAppCloud;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Les 2 réglages du maquis, modifiables par le propriétaire
 * (et le Super Admin) : mode de service et ardoise.
 */
class RestaurantSettings extends Component
{
    use \App\Livewire\Concerns\ShowsBusinessErrors;

    public string $serviceMode = 'both';
    public bool $creditEnabled = false;
    public string $paymentTiming = 'before';
    public bool $whatsappReportEnabled = true;
    public string $reportPhone = '';

    public function mount(): void
    {
        $restaurant = $this->restaurant();

        $this->serviceMode = $restaurant->service_mode;
        $this->creditEnabled = $restaurant->creditEnabled();
        $this->paymentTiming = $restaurant->payment_timing;
        $this->whatsappReportEnabled = (bool) $restaurant->whatsapp_report_enabled;
        $this->reportPhone = (string) $restaurant->report_phone;
    }

    private function restaurant(): Restaurant
    {
        return auth()->user()->currentRestaurant() ?? abort(403);
    }

    public function save()
    {
        $this->reportPhone = trim($this->reportPhone) !== '' ? User::normalizePhone($this->reportPhone) : '';

        $this->validate([
            'serviceMode' => ['required', Rule::in(array_keys(Restaurant::SERVICE_MODES))],
            'creditEnabled' => 'boolean',
            'paymentTiming' => ['required', Rule::in(array_keys(Restaurant::PAYMENT_TIMINGS))],
            'whatsappReportEnabled' => 'boolean',
            'reportPhone' => ['nullable', 'regex:' . User::PHONE_REGEX],
        ], [
            'reportPhone.regex' => 'Numéro invalide : 10 chiffres, par exemple 07 11 22 33 44.',
        ]);

        $restaurant = $this->restaurant();

        // Désactiver l'ardoise cacherait des dettes encore dues
        if (! $this->creditEnabled && $restaurant->creditEnabled()) {
            $due = (int) \App\Models\CreditTransaction::query()
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'charge' THEN amount ELSE -amount END), 0) as due")
                ->value('due');

            if ($due > 0) {
                $this->addError('creditEnabled', 'Impossible pour l\'instant : des clients doivent encore '
                    . number_format($due, 0, ',', ' ') . ' FCFA à crédit.');

                return;
            }
        }

        // On ne change pas de fonctionnement avec des bons en attente au comptoir
        if ($this->paymentTiming !== $restaurant->payment_timing) {
            $pending = Order::where('status', 'awaiting_payment')->count();

            if ($pending > 0) {
                $this->addError('paymentTiming', "Impossible pour l'instant : {$pending} " . ($pending > 1 ? 'commandes attendent' : 'commande attend') . " d'être encaissée" . ($pending > 1 ? 's' : '') . ". Encaissez-les d'abord.");

                return;
            }
        }

        // On ne retire pas les tables tant que des clients y sont encore servis
        if ($this->serviceMode === 'quick' && $restaurant->usesTables()) {
            $openTableOrders = Order::whereNotNull('table_id')
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->count();

            if ($openTableOrders > 0) {
                $this->addError('serviceMode', "Impossible pour l'instant : {$openTableOrders} " . ($openTableOrders > 1 ? 'commandes de table ne sont pas encore payées' : 'commande de table n\'est pas encore payée') . ". Encaissez-les d'abord.");

                return;
            }
        }

        $restaurant->update([
            'service_mode' => $this->serviceMode,
            'credit_enabled' => $this->creditEnabled,
            'payment_timing' => $this->paymentTiming,
            'whatsapp_report_enabled' => $this->whatsappReportEnabled,
            'report_phone' => $this->reportPhone ?: null,
        ]);

        Log::info('Réglages du maquis modifiés', [
            'user_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
            'service_mode' => $this->serviceMode,
            'credit_enabled' => $this->creditEnabled,
            'payment_timing' => $this->paymentTiming,
        ]);

        $labels = ['service_mode' => 'mode de service', 'credit_enabled' => 'crédit client', 'payment_timing' => 'moment du paiement', 'whatsapp_report_enabled' => 'rapport WhatsApp', 'report_phone' => 'numéro du rapport'];
        $changed = array_values(array_intersect_key($labels, $restaurant->getChanges()));
        if ($changed !== []) {
            \App\Models\ActivityLog::record('settings_changed', 'a modifié les réglages : ' . implode(', ', $changed) . '.');
        }

        session()->flash('success', 'Réglages enregistrés.');
    }

    public function render()
    {
        return view('livewire.restaurant-settings', [
            'modes' => Restaurant::SERVICE_MODES,
            'timings' => Restaurant::PAYMENT_TIMINGS,
            'restaurant' => $this->restaurant()->loadMissing('plan'),
            'subscriptionPayments' => $this->restaurant()->subscriptionPayments()->with('plan')->latest()->limit(12)->get(),
            'plans' => \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'billing' => array_filter(['whatsapp' => \App\Support\SupportContact::whatsappNumber()] + config('maquis.billing', [])),
            'whatsappConfigured' => app(WhatsAppCloud::class)->isConfigured(),
            'ownerPhone' => $this->restaurant()->users()->where('role', 'owner')->where('status', 'active')->orderBy('id')->value('phone'),
        ]);
    }
}
