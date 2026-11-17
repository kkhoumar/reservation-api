<?php

namespace App\Livewire;

use App\Models\Zone;
use Livewire\Component;

class TablesBoard extends Component
{
    public function mount()
    {
        // Maquis sans tables : on va directement à la vente rapide
        if (! auth()->user()->currentRestaurant()?->usesTables()) {
            $this->redirect(route('orders.new'), navigate: false);
        }
    }

    public function render()
    {
        $zones = Zone::with(['tables' => fn ($q) => $q->orderBy('label')])
            ->where('restaurant_id', auth()->user()->currentRestaurantId())
            ->get();

        return view('livewire.tables-board', [
            'zones' => $zones,
            'quickSale' => (bool) auth()->user()->currentRestaurant()?->usesQuickSale(),
        ]);
    }
}