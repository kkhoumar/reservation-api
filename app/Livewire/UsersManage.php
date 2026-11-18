<?php

namespace App\Livewire;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Gestion du personnel. Les droits sont dans UserPolicy :
 * une gérante gère caissiers et serveurs, le propriétaire gère aussi les gérants.
 */
class UsersManage extends Component
{
    use \App\Livewire\Concerns\ShowsBusinessErrors;

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

        // Le numéro appartient à un ancien employé du maquis : on le réintègre plutôt
        $former = User::where('phone', $this->phone)
            ->where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereNotNull('left_at')
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->first();

        if ($former) {
            $this->addError('phone', "Ce numéro est celui de {$former->first_name} {$former->last_name}, ancien employé : réintégrez-le dans « Anciens employés ».");

            return;
        }

        $this->validate(messages: [
            'phone.regex' => 'Numéro invalide : 10 chiffres, par exemple 07 11 22 33 44.',
            'phone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
        ]);

        if (! $target && ! $this->ensureUserLimit()) {
            return;
        }

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

        if ($user->hasLeft()) {
            return;
        }

        if ($user->status !== 'active' && ! $this->ensureUserLimit()) {
            return;
        }

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
    }

    /**
     * La formule limite le nombre d'utilisateurs actifs (patron compris).
     * Désactiver ou retirer quelqu'un libère une place.
     */
    protected function ensureUserLimit(): bool
    {
        $restaurant = auth()->user()->currentRestaurant();

        if (! $restaurant || $restaurant->canAddActiveUser()) {
            return true;
        }

        $message = "Votre formule {$restaurant->plan->name} permet {$restaurant->userLimit()} utilisateurs actifs, et ils sont tous pris. "
            . 'Désactivez ou retirez quelqu\'un, ou passez à une formule supérieure.';

        $this->addError('limit', $message);
        session()->flash('error', $message);

        return false;
    }

    /**
     * Départ d'un employé : il ne peut plus se connecter et sort de la liste,
     * mais son historique (ventes, caisse, crédits) reste intact.
     */
    public function removeUser($userId)
    {
        $user = $this->findStaff((int) $userId);
        Gate::authorize('remove', $user);

        if ($user->hasLeft()) {
            return;
        }

        if ($user->isServer() && ($balance = $user->sacocheBalance()) > 0) {
            session()->flash('error', "{$user->first_name} doit encore verser " . number_format($balance, 0, ',', ' ')
                . ' FCFA de sa sacoche. Recevez son versement avant de la retirer.');

            return;
        }

        $user->forceFill(['status' => 'inactive', 'left_at' => now()])->save();

        Log::info('Employé retiré du maquis', [
            'by' => auth()->id(),
            'user_id' => $user->id,
            'role' => $user->role,
            'restaurant_id' => $user->restaurant_id,
        ]);

        if ($this->editingId === $user->id) {
            $this->showForm = false;
        }

        session()->flash('success', "{$user->first_name} {$user->last_name} a été retiré(e) du maquis. Son historique est conservé.");
    }

    /** Un ancien employé revient : il retrouve son compte et son historique. */
    public function restoreUser($userId)
    {
        $user = $this->findStaff((int) $userId);
        Gate::authorize('remove', $user);

        if (! $this->ensureUserLimit()) {
            return;
        }

        $user->forceFill(['status' => 'active', 'left_at' => null])->save();

        session()->flash('success', "{$user->first_name} {$user->last_name} fait de nouveau partie de l'équipe.");
    }

    public function render()
    {
        $actor = auth()->user();
        $all = User::where('restaurant_id', $actor->currentRestaurantId())
            ->orderBy('role')
            ->orderBy('first_name')
            ->get();

        return view('livewire.users-manage', [
            'users' => $all->whereNull('left_at')->values(),
            'formerUsers' => $all->whereNotNull('left_at')->sortByDesc('left_at')->values(),
            'assignableRoles' => UserPolicy::assignableRoles($actor),
            'restaurant' => $actor->currentRestaurant()?->loadMissing('plan'),
        ]);
    }
}
