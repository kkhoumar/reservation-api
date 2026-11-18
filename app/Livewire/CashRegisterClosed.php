<?php

namespace App\Livewire;

use App\Jobs\SendRegisterReport;
use App\Models\CashRegister;
use App\Services\WhatsAppCloud;
use App\Support\RegisterReport;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Caisse clôturée + rapport du soir : envoi automatique au patron sur
 * WhatsApp (si configuré), et boutons manuels toujours disponibles.
 */
class CashRegisterClosed extends Component
{
    use \App\Livewire\Concerns\ShowsBusinessErrors;

    #[Locked]
    public int $registerId;

    public function mount($register)
    {
        // La caisse doit appartenir au restaurant de l'utilisateur (sinon 404)
        $this->registerId = CashRegister::findOrFail($register)->id;
    }

    /** Relancer l'envoi automatique (après un échec, ou s'il n'a pas eu lieu). */
    public function resend(WhatsAppCloud $whatsapp): void
    {
        $register = CashRegister::findOrFail($this->registerId);

        if (! $whatsapp->isConfigured() || $register->report_status === 'sent' || $register->report_status === 'pending') {
            return;
        }

        SendRegisterReport::queueFor($register);
        session()->flash('success', 'Envoi du rapport relancé.');
    }

    public function render(WhatsAppCloud $whatsapp)
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
            'autoAvailable' => $whatsapp->isConfigured() && $register->restaurant?->whatsapp_report_enabled && $register->restaurant->hasAutoReport(),
        ]);
    }
}
