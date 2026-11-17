<?php

namespace App\Livewire;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Gestion du personnel. Les droits sont dans UserPolicy :
 * une gérante gère caissiers et serveurs, le propriétaire gère aussi les gérants.
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
        $editingSelf = $this->editingId === auth()->id();

        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'phone' => ['required', 'regex:' . User::PHONE_REGEX, Rule::unique('users', 'phone')->ignore($this->editingId)],
            'password' => $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8',
            // On ne peut attribuer que les rôles autorisés ; son propre rôle ne change pas
            'role' => $editingSelf ? 'nullable' : ['required', Rule::in(UserPolicy::assignableRoles(auth()->user()))],
        ];
    }

    private function findStaff(int $userId): User
    {
        return User::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($userId);
    }

    public function openCreateForm()
    {
        Gate::authorize('create', User::class);

        $this->reset(['editingId', 'firstName', 'lastName', 'phone', 'password', 'role']);
        $this->role = 'server';
        $this->showForm = true;
    }

    public function editUser($userId)
    {
        $user = $this->findStaff((int) $userId);
        Gate::authorize('update', $user);

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
        $target = $this->editingId ? $this->findStaff($this->editingId) : null;
        $target ? Gate::authorize('update', $target) : Gate::authorize('create', User::class);

        $this->phone = User::normalizePhone($this->phone);
        $this->validate(messages: [
            'phone.regex' => 'Numéro invalide : 10 chiffres, par exemple 07 11 22 33 44.',
            'phone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
        ]);

        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
        ];

        if ($this->editingId !== auth()->id()) {
            $data['role'] = $this->role;
        }

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        // Mot de passe choisi par quelqu'un d'autre = provisoire
        $mustChange = $this->password !== '' && $this->editingId !== auth()->id();

        if ($target) {
            $target->update($data);
        } else {
            $data['restaurant_id'] = auth()->user()->currentRestaurantId();
            $target = User::create($data);
        }

        if ($mustChange) {
            $target->forceFill(['must_change_password' => true])->save();
        }

        $this->showForm = false;
        session()->flash('success', 'Utilisateur enregistré.');
    }

    public function toggleStatus($userId)
    {
        $user = $this->findStaff((int) $userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            return;
        }

        Gate::authorize('toggleStatus', $user);

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
    }

    public function render()
    {
        $actor = auth()->user();
        $users = User::where('restaurant_id', $actor->currentRestaurantId())
            ->orderBy('role')
            ->orderBy('first_name')
            ->get();

        return view('livewire.users-manage', [
            'users' => $users,
            'assignableRoles' => UserPolicy::assignableRoles($actor),
        ]);
    }
}
