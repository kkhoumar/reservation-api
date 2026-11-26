<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900; // 15 minutes

    /** Même en changeant de connexion internet : 20 essais ratés par numéro et par heure. */
    private const MAX_ATTEMPTS_PER_PHONE = 20;
    private const PHONE_DECAY_SECONDS = 3600;

    public string $phone = '';
    public string $password = '';

    /** « Rester connecté sur cet appareil » (téléphone personnel de la serveuse…). */
    public bool $remember = false;

    public function authenticate()
    {
        $this->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // Anti force brute : 5 essais par numéro + IP, puis blocage temporaire
        $key = $this->throttleKey();
        $phoneKey = 'login-phone:' . User::normalizePhone($this->phone);

        $blocked = match (true) {
            RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS) => $key,
            RateLimiter::tooManyAttempts($phoneKey, self::MAX_ATTEMPTS_PER_PHONE) => $phoneKey,
            default => null,
        };

        if ($blocked) {
            $minutes = (int) ceil(RateLimiter::availableIn($blocked) / 60);
            $this->addError('phone', "Trop de tentatives. Réessayez dans {$minutes} minute" . ($minutes > 1 ? 's' : '') . '.');
            $this->dispatch('me-wa-help'); // bulle « Problème pour vous connecter ? »
            return;
        }

        // Seuls les comptes actifs peuvent se connecter
        if (!Auth::attempt(['phone' => User::normalizePhone($this->phone), 'password' => $this->password, 'status' => 'active'], $this->remember)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            RateLimiter::hit($phoneKey, self::PHONE_DECAY_SECONDS);
            $this->addError('phone', 'Numéro de téléphone ou mot de passe incorrect.');

            // 2 essais ratés : on propose l'aide sur WhatsApp
            if (RateLimiter::attempts($key) >= 2) {
                $this->dispatch('me-wa-help');
            }

            return;
        }

        RateLimiter::clear($key);
        RateLimiter::clear($phoneKey);
        session()->regenerate();

        \App\Models\ActivityLog::record('login', "s'est connecté.", ['user' => Auth::user()]);

        // Le Super Admin arrive sur la liste des maquis
        // Mot de passe provisoire : à changer avant tout
        $destination = match (true) {
            Auth::user()->mustChangePassword() => 'password.change',
            Auth::user()->isPlatformAdmin() => 'admin.restaurants',
            default => 'dashboard.index',
        };

        $this->redirect(route($destination), navigate: false);
    }

    private function throttleKey(): string
    {
        return 'login:' . User::normalizePhone($this->phone) . '|' . request()->ip();
    }

    public function render()
    {
        $whatsapp = \App\Support\SupportContact::whatsappNumber();

        return view('livewire.login', [
            // Le numéro tapé est ajouté au message dans la page (voir la vue)
            'supportUrl' => $whatsapp ? 'https://wa.me/225' . User::normalizePhone($whatsapp) . '?text=' : null,
            'supportText' => "Bonjour, j'ai oublié le mot de passe de mon compte patron Maquis Express.\nMon numéro de compte : ",
        ]);
    }
}