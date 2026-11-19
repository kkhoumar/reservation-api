<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesAvatarUpload;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Mon profil (tous les comptes) : photo, nom, numéro et mot de passe.
 */
class Profile extends Component
{
    use HandlesAvatarUpload;

    public string $firstName = '';
    public string $lastName = '';
    public string $phone = '';

    public string $currentPassword = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->firstName = (string) $user->first_name;
        $this->lastName = (string) $user->last_name;
        $this->phone = (string) $user->phone;
    }

    /** @return string|null message d'erreur */
    public function savePhoto(string $dataUrl): ?string
    {
        $error = $this->storeAvatar(auth()->user(), $dataUrl);

        if (! $error) {
            $this->dispatch('avatar-updated', url: auth()->user()->avatarUrl());
        }

        return $error;
    }

    public function removePhoto(): void
    {
        $this->deleteAvatar(auth()->user());
        $this->dispatch('avatar-updated', url: null);
    }

    public function saveInfo(): void
    {
        $user = auth()->user();
        $this->phone = User::normalizePhone($this->phone);

        $this->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'phone' => ['required', 'regex:' . User::PHONE_REGEX, Rule::unique('users', 'phone')->ignore($user->id)],
        ], [
            'firstName.required' => 'Entrez votre prénom.',
            'lastName.required' => 'Entrez votre nom.',
            'phone.required' => 'Entrez votre numéro.',
            'phone.regex' => 'Numéro invalide : 10 chiffres, par exemple 07 11 22 33 44.',
            'phone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
        ]);

        $phoneChanged = $user->phone !== $this->phone;

        $user->forceFill([
            'first_name' => trim($this->firstName),
            'last_name' => trim($this->lastName),
            'phone' => $this->phone,
        ])->save();

        if ($phoneChanged) {
            ActivityLog::record('user_updated', 'a changé son numéro de téléphone de connexion.');
        }

        session()->flash('profile-success', $phoneChanged
            ? 'Informations enregistrées. Connectez-vous désormais avec le ' . $this->phone . '.'
            : 'Informations enregistrées.');
    }

    public function savePassword(): void
    {
        $user = auth()->user();

        $this->validate([
            'currentPassword' => 'required|string',
            'password' => 'required|string|min:8|max:100|same:passwordConfirmation|different:currentPassword',
        ], [
            'currentPassword.required' => 'Entrez votre mot de passe actuel.',
            'password.required' => 'Entrez le nouveau mot de passe.',
            'password.min' => 'Au moins 8 caractères.',
            'password.same' => 'Les deux mots de passe ne correspondent pas.',
            'password.different' => 'Choisissez un mot de passe différent de l\'actuel.',
        ]);

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Mot de passe actuel incorrect.');

            return;
        }

        $user->forceFill(['password' => $this->password, 'must_change_password' => false])->save();
        ActivityLog::record('password_changed', 'a changé son mot de passe.');

        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        session()->flash('password-success', 'Mot de passe changé.');
    }

    public function render()
    {
        $user = auth()->user();
        $roles = ['owner' => 'Patron', 'manager' => 'Gérante', 'cashier' => 'Caisse et comptoir', 'server' => 'Serveuse'];

        return view('livewire.profile', [
            'user' => $user,
            'roleLabel' => $user->isPlatformAdmin() ? 'Super Admin' : ($roles[$user->role] ?? $user->role),
            'restaurant' => $user->restaurant,
        ]);
    }
}
