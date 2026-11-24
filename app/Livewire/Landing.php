<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Page d'accueil publique : présentation de Maquis Express aux patrons.
 * Les tarifs affichés viennent des formules réelles (modifiables par le Super Admin).
 */
#[Layout('components.layouts.marketing')]
class Landing extends Component
{
    public function render()
    {
        $whatsapp = \App\Support\SupportContact::whatsappNumber();

        return view('livewire.landing', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'trialDays' => Restaurant::DEFAULT_TRIAL_DAYS,
            'whatsappUrl' => $whatsapp
                ? 'https://wa.me/225' . User::normalizePhone($whatsapp) . '?text=' . rawurlencode('Bonjour, je voudrais en savoir plus sur Maquis Express pour mon maquis.')
                : null,
        ]);
    }
}
