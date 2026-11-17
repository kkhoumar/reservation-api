<?php

namespace App\Livewire\Admin;

use App\Actions\CreateRestaurantWithOwner;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Super Admin : installer un nouveau client (maquis + propriétaire).
 * Après création, affiche une seule fois les identifiants à transmettre.
 */
class CreateRestaurant extends Component
{
    public const TYPES = [
        'maquis' => 'Maquis',
        'bar_restaurant' => 'Bar-restaurant',
        'restaurant' => 'Restaurant',
        'snack' => 'Snack',
    ];

    // Maquis
    public string $name = '';
    public string $type = 'maquis';
    public string $restaurantPhone = '';
    public string $commune = '';
    public string $city = 'Abidjan';
    public int $trialDays = 14;

    // Propriétaire
    public string $ownerFirstName = '';
    public string $ownerLastName = '';
    public string $ownerPhone = '';

    // Résultat, affiché une seule fois
    #[Locked]
    public ?array $created = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'restaurantPhone' => ['required', 'regex:' . User::PHONE_REGEX],
            'commune' => 'nullable|string|max:80',
            'city' => 'nullable|string|max:80',
            'trialDays' => 'required|integer|min:0|max:90',
            'ownerFirstName' => 'required|string|max:80',
            'ownerLastName' => 'required|string|max:80',
            'ownerPhone' => ['required', 'regex:' . User::PHONE_REGEX, Rule::unique('users', 'phone')],
        ];
    }

    protected function messages(): array
    {
        return [
            'restaurantPhone.regex' => 'Numéro invalide : 10 chiffres, par exemple 07 11 22 33 44.',
            'ownerPhone.regex' => 'Numéro invalide : 10 chiffres, par exemple 07 11 22 33 44.',
            'ownerPhone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
        ];
    }

    public function save(CreateRestaurantWithOwner $action)
    {
        // Même numéro quelle que soit la façon de l'écrire
        $this->restaurantPhone = User::normalizePhone($this->restaurantPhone);
        $this->ownerPhone = User::normalizePhone($this->ownerPhone);

        $this->validate();

        $result = $action->handle(
            [
                'name' => trim($this->name),
                'type' => $this->type,
                'phone' => $this->restaurantPhone,
                'commune' => trim($this->commune),
                'city' => trim($this->city),
                'trial_days' => $this->trialDays,
            ],
            [
                'first_name' => trim($this->ownerFirstName),
                'last_name' => trim($this->ownerLastName),
                'phone' => $this->ownerPhone,
            ],
        );

        $this->created = [
            'restaurant_name' => $result['restaurant']->name,
            'owner_name' => $result['owner']->first_name,
            'phone' => $result['owner']->phone,
            'password' => $result['password'],
            'trial_ends_at' => $result['restaurant']->trial_ends_at?->format('d/m/Y'),
        ];
    }

    public function whatsappMessage(): string
    {
        if (! $this->created) {
            return '';
        }

        $c = $this->created;
        $lines = [
            "Bonjour {$c['owner_name']}, bienvenue sur Maquis Express !",
            "Votre espace « {$c['restaurant_name']} » est prêt.",
            '',
            'Connexion : ' . route('login'),
            "Téléphone : {$c['phone']}",
            "Mot de passe provisoire : {$c['password']}",
            '',
            'Vous devrez choisir votre propre mot de passe à la première connexion.',
        ];

        if ($c['trial_ends_at']) {
            $lines[] = "Essai gratuit jusqu'au {$c['trial_ends_at']}.";
        }

        return implode("\n", $lines);
    }

    public function render()
    {
        return view('livewire.admin.create-restaurant', [
            'types' => self::TYPES,
            'whatsapp' => $this->whatsappMessage(),
        ]);
    }
}
