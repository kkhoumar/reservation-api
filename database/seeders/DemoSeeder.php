<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Maquis de démonstration « Maquis Chez Koffi ».
 *
 * - Mot de passe « password » seulement sur l'ordinateur du développeur.
 *   Sur un serveur en ligne : refuse, sauf si DEMO_PASSWORD (8 caractères ou plus)
 *   est réglé dans Render ; ce mot de passe est alors utilisé.
 * - Peut être relancé sans créer de doublon : si le patron de démo
 *   existe déjà, rien n'est fait.
 */
class DemoSeeder extends Seeder
{
    public const ACCOUNTS = [
        'owner' => ['Koffi', 'Kouassi', '0711111111'],
        'manager' => ['Mariam', 'Bamba', '0744444444'],
        'server' => ['Aya', 'Konan', '0722222222'],
        'cashier' => ['Jean', 'Yao', '0733333333'],
    ];

    public function run(): void
    {
        $password = self::password();

        if ($password === null) {
            $this->command?->error('Maquis de démo refusé sur un serveur en ligne : réglez DEMO_PASSWORD (8 caractères ou plus) pour le créer.');

            return;
        }

        if (User::where('phone', self::ACCOUNTS['owner'][2])->exists()) {
            $this->command?->warn('Le maquis de démo existe déjà : rien à faire.');
            $this->printAccounts();

            return;
        }

        DB::transaction(function () use ($password) {
            $restaurant = Restaurant::create([
                'name' => 'Maquis Chez Koffi',
                'type' => 'maquis',
                'phone' => '0700000000',
                'address' => 'Rue du Commerce',
                'commune' => 'Cocody',
                'city' => 'Abidjan',
                'service_mode' => 'both',
                'credit_enabled' => true,
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays(30)->endOfDay(),
            ]);

            foreach (self::ACCOUNTS as $role => [$first, $last, $phone]) {
                User::create([
                    'restaurant_id' => $restaurant->id,
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => $phone,
                    'password' => $password, // haché par le cast « hashed »
                    'role' => $role,
                    'status' => 'active',
                ]);
            }

            $zones = [
                'Salle principale' => range(1, 6),
                'Terrasse' => range(7, 10),
            ];

            foreach ($zones as $zoneName => $numbers) {
                $zone = Zone::create(['restaurant_id' => $restaurant->id, 'name' => $zoneName]);

                foreach ($numbers as $i) {
                    RestaurantTable::create([
                        'restaurant_id' => $restaurant->id,
                        'zone_id' => $zone->id,
                        'label' => "Table $i",
                        'capacity' => 4,
                    ]);
                }
            }

            $menu = [
                'Plats' => [
                    ['Attiéké Poisson', 3000, 50, 5, 1800],
                    ['Alloco', 1500, 40, 5, 800],
                    ['Poulet Braisé', 3500, 30, 5, 2200],
                ],
                'Boissons' => [
                    ['Bière', 1000, 100, 10, 600],
                    ['Jus de Bissap', 800, 25, 5, 300],
                ],
            ];

            $order = 1;
            foreach ($menu as $categoryName => $products) {
                $category = Category::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $categoryName,
                    'sort_order' => $order++,
                ]);

                foreach ($products as [$name, $price, $stock, $alert, $cost]) {
                    Product::create([
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $category->id,
                        'name' => $name,
                        'selling_price' => $price,
                        'cost_price' => $cost,
                        'stock_quantity' => $stock,
                        'stock_alert_threshold' => $alert,
                    ]);
                }
            }
        });

        $this->command?->info('Maquis de démo créé : Maquis Chez Koffi (essai de 30 jours).');
        $this->printAccounts();
    }

    /** Mot de passe des comptes de démo, ou null si on ne doit pas les créer ici. */
    public static function password(): ?string
    {
        if (app()->environment('local', 'testing')) {
            return 'password';
        }
        $password = (string) config('app.demo_password');

        return strlen($password) >= 8 && $password !== 'password' ? $password : null;
    }

    private function printAccounts(): void
    {
        $shown = app()->environment('local', 'testing') ? 'password' : '(DEMO_PASSWORD)';
        foreach (self::ACCOUNTS as $role => [$first, , $phone]) {
            $this->command?->line("  {$role} : {$first} · {$phone} / {$shown}");
        }
    }
}
