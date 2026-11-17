<?php

namespace App\Livewire;

use App\Models\Zone;
use Livewire\Attributes\On;
use Livewire\Component;

class TablesBoard extends Component
{
    #[On('user-switched')]
    public function refresh()
    {
        //
    }

    public function render()
    {
        $zones = Zone::with(['tables' => fn ($q) => $q->orderBy('label')])
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->get();

        return view('livewire.tables-board', ['zones' => $zones]);
    }
}