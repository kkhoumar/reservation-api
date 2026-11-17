<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;


/**
 * STOPGAP TEMPORAIRE : simule une connexion en attendant le vrai système
 * d'authentification (PRD section 5). Permet de choisir "je suis Aya, serveuse"
 * pour tester le flux commande sans construire le login tout de suite.
 *
 * À SUPPRIMER une fois l'authentification Laravel réelle en place.
 */
class ActingUserSwitcher extends Component
{
    public $userId = null;

    public function mount()
    {
        $this->userId = session('acting_user_id');
    }

    public function switchUser()
    {
        $user = User::find($this->userId);

        if ($user) {
            session([
                'acting_user_id' => $user->id,
                'acting_user_name' => $user->first_name . ' ' . $user->last_name,
                'acting_user_role' => $user->role,
                'acting_restaurant_id' => $user->restaurant_id,
            ]);

            $this->dispatch('user-switched');
        }
    }

    public function render()
    {
        return view('livewire.acting-user-switcher', [
            'users' => User::all(),
        ]);
    }
}
