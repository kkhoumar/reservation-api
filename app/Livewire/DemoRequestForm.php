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
#[Layout('components.layouts.guest')]
class DemoRequestForm extends Component
{
    private const MAX_PER_HOUR = 3;

    public string $contactName = '';
    public string $restaurantName = '';
    public string $phone = '';
    public string $commune = '';
    public string $message = '';

    /** Champ piège invisible : un humain le laisse vide, un robot le remplit. */
    public string $website = '';

    public bool $sent = false;

    public function submit()
    {
        $this->phone = User::normalizePhone($this->phone);

        $this->validate([
            'contactName' => 'required|string|max:120',
            'restaurantName' => 'required|string|max:120',
            'phone' => ['required', 'regex:' . User::PHONE_REGEX],
            'commune' => 'nullable|string|max:80',
            'message' => 'nullable|string|max:500',
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
                'message' => trim($this->message) ?: null,
            ]);

            Log::info('Nouvelle demande de démo', ['demo_request_id' => $request->id]);
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.demo-request-form');
    }
}
