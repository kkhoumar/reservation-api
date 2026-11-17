<?php

namespace App\Livewire;

use App\Models\CashRegister;
use Livewire\Component;

class CashRegisterClosed extends Component
{
    public int $registerId;

    public function mount($register)
    {
        $this->registerId = $register;
    }

    public function render()
    {
        $register = CashRegister::with(['openedByUser', 'closedByUser'])->findOrFail($this->registerId);

        return view('livewire.cash-register-closed', ['register' => $register]);
    }
}