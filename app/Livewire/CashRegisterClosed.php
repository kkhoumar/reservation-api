<?php

namespace App\Livewire;

use App\Models\CashRegister;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CashRegisterClosed extends Component
{
    #[Locked]
    public int $registerId;

    public function mount($register)
    {
        // La caisse doit appartenir au restaurant de l'utilisateur (sinon 404)
        $this->registerId = CashRegister::findOrFail($register)->id;
    }

    public function render()
    {
        $register = CashRegister::with(['openedByUser', 'closedByUser'])->findOrFail($this->registerId);

        return view('livewire.cash-register-closed', ['register' => $register]);
    }
}