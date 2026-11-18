<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Support\RegisterReport;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Caisse clôturée + rapport du soir, prêt à partir chez le patron sur WhatsApp.
 */
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
        $register = CashRegister::with(['openedByUser', 'closedByUser', 'restaurant'])->findOrFail($this->registerId);
        $report = new RegisterReport($register);

        return view('livewire.cash-register-closed', [
            'register' => $register,
            'report' => $report,
            'reportText' => $report->toWhatsappText(),
            'ownerUrl' => $report->whatsappToOwnerUrl(),
            'shareUrl' => $report->whatsappShareUrl(),
            'isOwner' => auth()->user()->isOwner(),
        ]);
    }
}
