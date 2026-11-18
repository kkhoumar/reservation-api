<?php

namespace App\Livewire\Admin;

use App\Models\DemoRequest;
use App\Models\Restaurant;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Espace Super Admin : tous les maquis de la plateforme.
 * - Entrer dans un maquis pour voir et gérer toutes ses données.
 * - Verrouiller (suspendre) un maquis qui n'a pas payé, ou le réactiver.
 */
class RestaurantsList extends Component
{
    public function enterRestaurant($restaurantId)
    {
        $restaurant = Restaurant::findOrFail($restaurantId);

        session([User::ADMIN_RESTAURANT_SESSION_KEY => $restaurant->id]);

        Log::info('Super Admin entré dans un maquis', [
            'admin_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
        ]);

        $this->redirect(route('dashboard.index'), navigate: false);
    }

    public function toggleStatus($restaurantId)
    {
        $restaurant = Restaurant::findOrFail($restaurantId);

        // Bloqué (suspendu, essai terminé ou impayé) → réactivé ; sinon → suspendu
        $newStatus = $restaurant->isLocked() ? 'active' : 'suspended';

        $restaurant->update(['subscription_status' => $newStatus]);

        // Réactivation manuelle d'un impayé : sans échéance, sinon il resterait bloqué.
        // Pour un vrai paiement, utiliser l'écran Abonnement (qui fixe la date payée).
        if ($newStatus === 'active' && $restaurant->isSubscriptionExpired()) {
            $restaurant->forceFill(['paid_until' => null])->save();
        }

        Log::info('Super Admin : statut de maquis modifié', [
            'admin_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
            'status' => $newStatus,
        ]);

        session()->flash('success', $newStatus === 'suspended'
            ? "{$restaurant->name} est verrouillé : son personnel n'a plus accès à l'application."
            : "{$restaurant->name} est réactivé.");
    }

    /** Demande de démo : « contacté » ou « sans suite ». */
    public function setDemoStatus($demoRequestId, string $status)
    {
        if (! in_array($status, ['contacted', 'rejected'], true)) {
            return;
        }

        DemoRequest::findOrFail($demoRequestId)->forceFill(['status' => $status])->save();
    }

    public function render()
    {
        $restaurants = Restaurant::with('plan')
            ->withCount(['users as active_users_count' => fn ($q) => $q->where('status', 'active')->whereNull('left_at')])
            ->orderBy('name')
            ->get();

        $paying = $restaurants->filter(fn (Restaurant $r) => $r->subscription_status === 'active' && $r->plan && ! $r->isLocked());

        return view('livewire.admin.restaurants-list', [
            'restaurants' => $restaurants,
            'monthlyRevenue' => (int) $paying->sum(fn (Restaurant $r) => $r->plan->monthly_price),
            'payingCount' => $paying->count(),
            'collectedThisMonth' => (int) SubscriptionPayment::where('created_at', '>=', now()->startOfMonth())->sum('amount'),
            'trialCount' => $restaurants->filter(fn (Restaurant $r) => $r->subscription_status === 'trial' && ! $r->isLocked())->count(),
            'toChaseCount' => $restaurants->filter(fn (Restaurant $r) => $r->subscription_status !== 'suspended'
                && ($r->isSubscriptionExpired() || $r->isInGracePeriod() || ($r->subscription_status === 'active' && $r->needsRenewalReminder())))->count(),
            'demoRequests' => DemoRequest::whereIn('status', ['new', 'contacted'])->latest()->get(),
        ]);
    }
}
