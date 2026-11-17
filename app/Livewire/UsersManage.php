<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Gestion des utilisateurs (PRD section 20, MVP v3 priorité #1).
 * Permet au owner/manager de créer et désactiver des comptes
 * serveur/caissier sans passer par Tinker.
 */
class UsersManage extends Component
{
    public bool $showForm = false;
    #[Locked]
    public ?int $editingId = null;

    public string $firstName = '';
    public string $lastName = '';
    public string $phone = '';
    public string $password = '';
    public string $role = 'server';

    protected function rules(): array
    {
        $phoneRule = 'required|string|unique:users,phone';
        if ($this->editingId) {
            $phoneRule = 'required|string|unique:users,phone,' . $this->editingId;
        }

        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'phone' => $phoneRule,
            'password' => $this->editingId ? 'nullable|string|min:6' : 'required|string|min:6',
            'role' => 'required|in:manager,server,cashier',
        ];
    }

    public function openCreateForm()
    {
        $this->reset(['editingId', 'firstName', 'lastName', 'phone', 'password', 'role']);
        $this->role = 'server';
        $this->showForm = true;
    }

    public function editUser($userId)
    {
        $user = User::where('restaurant_id', auth()->user()->restaurant_id)->findOrFail($userId);

        $this->editingId = $user->id;
        $this->firstName = $user->first_name;
        $this->lastName = $user->last_name;
        $this->phone = $user->phone;
        $this->password = '';
        $this->role = $user->role;
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            User::where('restaurant_id', auth()->user()->restaurant_id)
                ->findOrFail($this->editingId)
                ->update($data);
        } else {
            $data['restaurant_id'] = auth()->user()->restaurant_id;
            $data['password'] = Hash::make($this->password);
            User::create($data);
        }

        $this->showForm = false;
        session()->flash('success', 'Utilisateur enregistré.');
    }

    public function toggleStatus($userId)
    {
        $user = User::where('restaurant_id', auth()->user()->restaurant_id)->findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            return;
        }

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
    }

    public function render()
    {
        $users = User::where('restaurant_id', auth()->user()->restaurant_id)
            ->orderBy('role')
            ->orderBy('first_name')
            ->get();

        return view('livewire.users-manage', ['users' => $users]);
    }
}