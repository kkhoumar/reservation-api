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

        // Bons envoyés au comptoir et pas encore encaissés, par table
        $atCounter = \App\Models\Order::where('status', 'awaiting_payment')
            ->whereNotNull('table_id')
            ->selectRaw('table_id, count(*) as bons')
            ->groupBy('table_id')
            ->pluck('bons', 'table_id');

        return view('livewire.tables-board', [
            'zones' => $zones,
            'atCounter' => $atCounter,
            'quickSale' => (bool) auth()->user()->currentRestaurant()?->usesQuickSale(),
        ]);
    }
}