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

        if (!Auth::attempt(['phone' => $this->phone, 'password' => $this->password])) {
            $this->addError('phone', 'Numéro de téléphone ou mot de passe incorrect.');
            return;
        }

        session()->regenerate();

        $this->redirect(route('dashboard.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.login');
    }
}