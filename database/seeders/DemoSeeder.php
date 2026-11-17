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
 * - Refuse de tourner en production (mots de passe « password »).
 * - Peut être relancé sans créer de doublon : si le patron de démo
 *   existe déjà, rien n'est fait.
 */
class DemoSeeder extends Seeder
{
    private const ACCOUNTS = [
        'owner' => ['Koffi', 'Kouassi', '0711111111'],
        'manager' => ['Mariam', 'Bamba', '0744444444'],
        'server' => ['Aya', 'Konan', '0722222222'],
        'cashier' => ['Jean', 'Yao', '0733333333'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoSeeder refusé en production : il crée des comptes avec le mot de passe « password ».');

            return;
        }

        if (User::where('phone', self::ACCOUNTS['owner'][2])->exists()) {
            $this->command?->warn('Le maquis de démo existe déjà : rien à faire.');
            $this->printAccounts();

            return;
        }

        DB::transaction(function () {
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
                    'password' => 'password', // haché par le cast « hashed »
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
                    ['Attiéké Poisson', 3000, 50, 5],
                    ['Alloco', 1500, 40, 5],
                    ['Poulet Braisé', 3500, 30, 5],
                ],
                'Boissons' => [
                    ['Bière', 1000, 100, 10],
                    ['Jus de Bissap', 800, 25, 5],
                ],
            ];

            $order = 1;
            foreach ($menu as $categoryName => $products) {
                $category = Category::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $categoryName,
                    'sort_order' => $order++,
                ]);

                foreach ($products as [$name, $price, $stock, $alert]) {
                    Product::create([
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $category->id,
                        'name' => $name,
                        'selling_price' => $price,
                        'stock_quantity' => $stock,
                        'stock_alert_threshold' => $alert,
                    ]);
                }
            }
        });

        $this->command?->info('Maquis de démo créé : Maquis Chez Koffi (essai de 30 jours).');
        $this->printAccounts();
    }

    private function printAccounts(): void
    {
        foreach (self::ACCOUNTS as $role => [$first, , $phone]) {
            $this->command?->line("  {$role} : {$first} · {$phone} / password");
        }
    }
}
