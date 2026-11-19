<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Choix d'un mot de passe personnel. Obligatoire à la première
 * connexion (mot de passe provisoire donné par le Super Admin ou
 * par un responsable).
 */
#[Layout('components.layouts.guest')]
class ChangePassword extends Component
{
    public string $currentPassword = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public function save()
    {
        $user = auth()->user();

        $this->validate([
            'currentPassword' => 'required|string',
            'password' => 'required|string|min:8|max:100|same:passwordConfirmation|different:currentPassword',
        ], [
            'password.min' => 'Au moins 8 caractères.',
            'password.same' => 'Les deux mots de passe ne correspondent pas.',
            'password.different' => 'Choisissez un mot de passe différent du mot de passe provisoire.',
        ]);

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Mot de passe actuel incorrect.');

            return;
        }

        $user->forceFill([
            'password' => $this->password, // haché par le cast « hashed »
            'must_change_password' => false,
        ])->save();

        session()->regenerate();
        Log::info('Mot de passe changé', ['user_id' => $user->id]);
        \App\Models\ActivityLog::record('password_changed', 'a changé son mot de passe.', ['user' => $user]);

        $this->redirect(
            route($user->isPlatformAdmin() ? 'admin.restaurants' : 'dashboard.index'),
            navigate: false,
        );
    }

    public function render()
    {
        return view('livewire.change-password', [
            'forced' => auth()->user()->mustChangePassword(),
        ]);
    }
}
