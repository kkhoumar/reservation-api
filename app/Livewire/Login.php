<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $phone = '';
    public string $password = '';

    public function authenticate()
    {
        $this->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // Seuls les comptes actifs peuvent se connecter
        if (!Auth::attempt(['phone' => $this->phone, 'password' => $this->password, 'status' => 'active'])) {
            $this->addError('phone', 'Numéro de téléphone ou mot de passe incorrect.');
            return;
        }

        session()->regenerate();

        // Le Super Admin arrive sur la liste des maquis
        $destination = Auth::user()->isPlatformAdmin() ? 'admin.restaurants' : 'dashboard.index';

        $this->redirect(route($destination), navigate: false);
    }

    public function render()
    {
        return view('livewire.login');
    }
}