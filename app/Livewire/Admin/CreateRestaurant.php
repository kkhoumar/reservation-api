<?php

namespace App\Livewire\Admin;

use App\Actions\CreateRestaurantWithOwner;
use App\Models\DemoRequest;
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
    public string $serviceMode = 'both';
    public bool $creditEnabled = false;

    // Propriétaire
    public string $ownerFirstName = '';
    public string $ownerLastName = '';
    public string $ownerPhone = '';

    // Demande de démo d'origine (facultatif)
    #[Locked]
    public ?int $demoRequestId = null;

    // Résultat, affiché une seule fois
    #[Locked]
    public ?array $created = null;

    public function mount(): void
    {
        $demo = request()->integer('demande')
            ? DemoRequest::whereIn('status', ['new', 'contacted'])->find(request()->integer('demande'))
            : null;

        if (! $demo) {
            return;
        }

        // Pré-remplissage : « Koffi Yao » → prénom Koffi, nom Yao
        $parts = preg_split('/\s+/', trim($demo->contact_name), 2);

        $this->demoRequestId = $demo->id;
        $this->name = $demo->restaurant_name;
        $this->commune = (string) $demo->commune;
        $this->restaurantPhone = $demo->phone;
        $this->ownerPhone = $demo->phone;
        $this->ownerFirstName = $parts[0] ?? '';
        $this->ownerLastName = $parts[1] ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'restaurantPhone' => ['required', 'regex:' . User::PHONE_REGEX],
            'commune' => 'nullable|string|max:80',
            'city' => 'nullable|string|max:80',
            'trialDays' => 'required|integer|min:0|max:90',
            'serviceMode' => ['required', Rule::in(array_keys(\App\Models\Restaurant::SERVICE_MODES))],
            'creditEnabled' => 'boolean',
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
                'service_mode' => $this->serviceMode,
                'credit_enabled' => $this->creditEnabled,
            ],
            [
                'first_name' => trim($this->ownerFirstName),
                'last_name' => trim($this->ownerLastName),
                'phone' => $this->ownerPhone,
            ],
        );

        if ($this->demoRequestId) {
            DemoRequest::whereKey($this->demoRequestId)->update([
                'status' => 'converted',
                'restaurant_id' => $result['restaurant']->id,
            ]);
        }

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
            'modes' => \App\Models\Restaurant::SERVICE_MODES,
            'whatsapp' => $this->whatsappMessage(),
        ]);
    }
}
