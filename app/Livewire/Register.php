<?php

namespace App\Livewire;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Création de compte établissement (PRD section 5-6). Crée le Restaurant
 * ET le premier utilisateur (owner) en une fois, statut TRIAL par défaut
 * (PRD section 15 — abonnement géré manuellement au MVP).
 */
#[Layout('components.layouts.guest')]
class Register extends Component
{
    public string $restaurantName = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $phone = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public function register()
    {
        $this->validate([
            'restaurantName' => 'required|string|max:255',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6|same:passwordConfirmation',
        ], [
            'phone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
            'password.same' => 'Les mots de passe ne correspondent pas.',
        ]);

        $restaurant = Restaurant::create([
            'name' => $this->restaurantName,
            'phone' => $this->phone,
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $user = User::create([
            'restaurant_id' => $restaurant->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'role' => 'owner',
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.register');
    }
}