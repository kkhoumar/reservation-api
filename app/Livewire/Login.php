<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900; // 15 minutes

    public string $phone = '';
    public string $password = '';

    public function authenticate()
    {
        $this->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // Anti force brute : 5 essais par numéro + IP, puis blocage temporaire
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('phone', "Trop de tentatives. Réessaie dans {$minutes} minute" . ($minutes > 1 ? 's' : '') . '.');
            return;
        }

        // Seuls les comptes actifs peuvent se connecter
        if (!Auth::attempt(['phone' => $this->phone, 'password' => $this->password, 'status' => 'active'])) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            $this->addError('phone', 'Numéro de téléphone ou mot de passe incorrect.');
            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();

        // Le Super Admin arrive sur la liste des maquis
        $destination = Auth::user()->isPlatformAdmin() ? 'admin.restaurants' : 'dashboard.index';

        $this->redirect(route($destination), navigate: false);
    }

    private function throttleKey(): string
    {
        return 'login:' . Str::lower(preg_replace('/\s+/', '', $this->phone)) . '|' . request()->ip();
    }

    public function render()
    {
        return view('livewire.login');
    }
}