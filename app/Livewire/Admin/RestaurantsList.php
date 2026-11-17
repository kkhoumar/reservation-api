<?php

namespace App\Livewire\Admin;

use App\Models\Restaurant;
use Livewire\Component;

class RestaurantsList extends Component
{
    public function toggleStatus($restaurantId)
    {
        $restaurant = Restaurant::findOrFail($restaurantId);

        $restaurant->update([
            'subscription_status' => $restaurant->subscription_status === 'suspended' ? 'active' : 'suspended',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.restaurants-list', [
            'restaurants' => Restaurant::withCount('users')->orderBy('name')->get(),
        ]);
    }
}