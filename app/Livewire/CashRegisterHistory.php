<?php

namespace App\Livewire;

use App\Models\CashRegister;
use Livewire\Component;
use Livewire\WithPagination;

class CashRegisterHistory extends Component
{
    use WithPagination;

    public function render()
    {
        $registers = CashRegister::with(['openedByUser', 'closedByUser'])
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->paginate(15);

        return view('livewire.cash-register-history', ['registers' => $registers]);
    }
}