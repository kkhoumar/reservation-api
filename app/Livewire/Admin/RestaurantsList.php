<?php

namespace App\Livewire\Admin;

use App\Models\Restaurant;
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

        $newStatus = $restaurant->subscription_status === 'suspended' ? 'active' : 'suspended';

        $restaurant->update(['subscription_status' => $newStatus]);

        Log::info('Super Admin : statut de maquis modifié', [
            'admin_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
            'status' => $newStatus,
        ]);

        session()->flash('success', $newStatus === 'suspended'
            ? "{$restaurant->name} est verrouillé : son personnel n'a plus accès à l'application."
            : "{$restaurant->name} est réactivé.");
    }

    public function render()
    {
        return view('livewire.admin.restaurants-list', [
            'restaurants' => Restaurant::withCount('users')->orderBy('name')->get(),
        ]);
    }
}
