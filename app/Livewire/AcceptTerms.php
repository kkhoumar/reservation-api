<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Le patron accepte les conditions d'utilisation et la politique de
 * confidentialité (première connexion, puis à chaque nouvelle version).
 */
#[Layout('components.layouts.guest')]
class AcceptTerms extends Component
{
    public bool $accepted = false;

    public function mount(): void
    {
        if (! auth()->user()->mustAcceptTerms()) {
            $this->redirect(route('dashboard.index'), navigate: false);
        }
    }

    public function accept(): void
    {
        $this->validate(['accepted' => 'accepted'], [
            'accepted.accepted' => 'Cochez la case pour continuer.',
        ]);

        $user = auth()->user();
        $version = config('maquis.terms_version');

        $user->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => $version,
        ])->save();

        Log::info('Conditions d\'utilisation acceptées', [
            'user_id' => $user->id,
            'restaurant_id' => $user->restaurant_id,
            'version' => $version,
            'ip' => request()->ip(),
        ]);

        $this->redirect(route('dashboard.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.accept-terms', [
            'isUpdate' => auth()->user()->terms_version !== null,
        ]);
    }
}
