<?php

namespace App\Livewire\Admin;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\SupportContact;
use Livewire\Component;

/**
 * Super Admin : réglages de la plateforme, sans toucher au serveur.
 * Pour l'instant : le numéro WhatsApp du service client (bouton vert en bas
 * à droite, liens « Nous écrire sur WhatsApp »).
 */
class PlatformSettings extends Component
{
    public string $whatsapp = '';

    /** Double protection (en plus du filtre de la route) : Super Admin seulement. */
    public function boot(): void
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);
    }

    public function mount(): void
    {
        $this->whatsapp = (string) PlatformSetting::read(PlatformSetting::SUPPORT_WHATSAPP, '');
    }

    public function save(): void
    {
        $this->whatsapp = User::normalizePhone($this->whatsapp);

        $this->validate([
            'whatsapp' => ['nullable', 'regex:/^0\d{9}$/'],
        ], [
            'whatsapp.regex' => 'Numéro ivoirien à 10 chiffres, par exemple 07 00 00 00 00.',
        ]);

        PlatformSetting::write(PlatformSetting::SUPPORT_WHATSAPP, $this->whatsapp ?: null);

        \App\Support\Toast::success($this->whatsapp
            ? 'Numéro enregistré. Le bouton WhatsApp mène maintenant à ce numéro.'
            : 'Numéro retiré. Le numéro du serveur est utilisé s\'il existe, sinon le bouton est caché.');
    }

    public function render()
    {
        return view('livewire.admin.platform-settings', [
            'testUrl' => SupportContact::whatsappUrl('Test du bouton WhatsApp de Maquis Express.'),
            'activeNumber' => SupportContact::whatsappNumber(),
        ]);
    }
}
