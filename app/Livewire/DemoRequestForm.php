<?php

namespace App\Livewire;

use App\Models\DemoRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Remplace l'inscription libre : le prospect laisse ses coordonnées,
 * le Super Admin le rappelle et crée son maquis.
 */
#[Layout('components.layouts.marketing')]
class DemoRequestForm extends Component
{
    private const MAX_PER_HOUR = 3;

    public const TYPES = ['Maquis', 'Bar', 'Restaurant', 'Lounge', 'Fast-food', 'Autre'];

    public const TEAM_SIZES = [
        'seul' => 'Je suis seul(e)',
        '2-5' => '2 à 5 personnes',
        '6-10' => '6 à 10 personnes',
        '10+' => 'Plus de 10 personnes',
    ];

    public string $contactName = '';
    public string $restaurantName = '';
    public string $phone = '';
    public string $commune = '';
    public string $message = '';
    public string $establishmentType = '';
    public string $teamSize = '';

    /** Champ piège invisible : un humain le laisse vide, un robot le remplit. */
    public string $website = '';

    public bool $sent = false;

    /** Type, taille de l'équipe et message libre, réunis pour le Super Admin. */
    protected function composedMessage(): ?string
    {
        $parts = array_filter([
            $this->establishmentType !== '' ? 'Type : ' . $this->establishmentType : null,
            $this->teamSize !== '' ? 'Équipe : ' . self::TEAM_SIZES[$this->teamSize] : null,
            trim($this->message) ?: null,
        ]);

        return $parts === [] ? null : mb_substr(implode(' · ', $parts), 0, 500);
    }

    public function submit()
    {
        $this->phone = User::normalizePhone($this->phone);

        $this->validate([
            'contactName' => 'required|string|max:120',
            'restaurantName' => 'required|string|max:120',
            'phone' => ['required', 'regex:' . User::PHONE_REGEX],
            'commune' => 'nullable|string|max:80',
            'message' => 'nullable|string|max:400',
            'establishmentType' => ['nullable', \Illuminate\Validation\Rule::in(self::TYPES)],
            'teamSize' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(self::TEAM_SIZES))],
        ], [
            'phone.regex' => 'Numéro invalide : 10 chiffres, par exemple 07 11 22 33 44.',
        ]);

        // Robot : on fait comme si tout allait bien, sans rien enregistrer
        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        $key = 'demo-request:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            $this->addError('phone', 'Trop de demandes envoyées. Réessaie plus tard ou appelle-nous directement.');

            return;
        }

        RateLimiter::hit($key, 3600);

        // Même numéro déjà en attente : pas de doublon
        $request = DemoRequest::where('phone', $this->phone)
            ->whereIn('status', ['new', 'contacted'])
            ->first();

        if (! $request) {
            $request = DemoRequest::create([
                'contact_name' => trim($this->contactName),
                'restaurant_name' => trim($this->restaurantName),
                'phone' => $this->phone,
                'commune' => trim($this->commune) ?: null,
                'message' => $this->composedMessage(),
            ]);

            Log::info('Nouvelle demande de démo', ['demo_request_id' => $request->id]);

            // Prévenir le Super Admin (en arrière-plan : le prospect n'attend pas)
            \App\Jobs\AlertNewDemoRequest::dispatch($request->id)->afterCommit();
        }

        $this->sent = true;
    }

    public function render()
    {
        $whatsapp = config('maquis.billing.whatsapp');

        return view('livewire.demo-request-form', [
            'types' => self::TYPES,
            'teamSizes' => self::TEAM_SIZES,
            'trialDays' => \App\Models\Restaurant::DEFAULT_TRIAL_DAYS,
            'whatsappUrl' => $whatsapp
                ? 'https://wa.me/225' . User::normalizePhone($whatsapp) . '?text=' . rawurlencode('Bonjour, je voudrais une démo de Maquis Express pour mon établissement.')
                : null,
        ]);
    }
}
