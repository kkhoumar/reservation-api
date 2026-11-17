<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Maquis Chez Koffi',
            'type' => 'maquis',
            'phone' => '+225 07 00 00 00 00',
            'address' => 'Rue du Commerce',
            'commune' => 'Cocody',
            'city' => 'Abidjan',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::create([
            'restaurant_id' => $restaurant->id,
            'first_name' => 'Koffi',
            'last_name' => 'Yao',
            'phone' => '+225 07 11 11 11 11',
            'email' => 'owner@maquis-koffi.ci',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        User::create([
            'restaurant_id' => $restaurant->id,
            'first_name' => 'Aya',
            'last_name' => 'Serveuse',
            'phone' => '+225 07 22 22 22 22',
            'password' => Hash::make('password'),
            'role' => 'server',
        ]);

        User::create([
            'restaurant_id' => $restaurant->id,
            'first_name' => 'Jean',
            'last_name' => 'Caissier',
            'phone' => '+225 07 33 33 33 33',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        $salle = Zone::create(['restaurant_id' => $restaurant->id, 'name' => 'Salle principale']);
        $terrasse = Zone::create(['restaurant_id' => $restaurant->id, 'name' => 'Terrasse']);

        foreach (range(1, 6) as $i) {
            RestaurantTable::create([
                'restaurant_id' => $restaurant->id,
                'zone_id' => $salle->id,
                'label' => "Table $i",
                'capacity' => 4,
            ]);
        }
        foreach (range(7, 10) as $i) {
            RestaurantTable::create([
                'restaurant_id' => $restaurant->id,
                'zone_id' => $terrasse->id,
                'label' => "Table $i",
                'capacity' => 4,
            ]);
        }

        $plats = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Plats', 'sort_order' => 1]);
        $boissons = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Boissons', 'sort_order' => 2]);

        Product::create([
            'restaurant_id' => $restaurant->id, 'category_id' => $plats->id,
            'name' => 'Attiéké Poisson', 'selling_price' => 3000,
            'stock_quantity' => 50, 'stock_alert_threshold' => 5,
        ]);
        Product::create([
            'restaurant_id' => $restaurant->id, 'category_id' => $plats->id,
            'name' => 'Alloco', 'selling_price' => 1500,
            'stock_quantity' => 40, 'stock_alert_threshold' => 5,
        ]);
        Product::create([
            'restaurant_id' => $restaurant->id, 'category_id' => $plats->id,
            'name' => 'Poulet Braisé', 'selling_price' => 3500,
            'stock_quantity' => 30, 'stock_alert_threshold' => 5,
        ]);
        Product::create([
            'restaurant_id' => $restaurant->id, 'category_id' => $boissons->id,
            'name' => 'Bière', 'selling_price' => 1000,
            'stock_quantity' => 100, 'stock_alert_threshold' => 10,
        ]);
        Product::create([
            'restaurant_id' => $restaurant->id, 'category_id' => $boissons->id,
            'name' => 'Jus de Bissap', 'selling_price' => 800,
            'stock_quantity' => 25, 'stock_alert_threshold' => 5,
        ]);

        $this->command->info('Restaurant pilote créé : Maquis Chez Koffi');
        $this->command->info('Owner : +225 07 11 11 11 11 / password');
        $this->command->info('Serveur : +225 07 22 22 22 22 / password');
        $this->command->info('Caissier : +225 07 33 33 33 33 / password');
    }
}