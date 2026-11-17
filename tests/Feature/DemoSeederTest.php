<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_seed_cree_le_maquis_de_demo_avec_ses_4_comptes(): void
    {
        $this->artisan('db:seed')->assertSuccessful();

        $koffi = Restaurant::sole();
        $this->assertSame('Maquis Chez Koffi', $koffi->name);
        $this->assertFalse($koffi->isLocked());
        $this->assertSame(['cashier', 'manager', 'owner', 'server'], User::orderBy('role')->pluck('role')->all());
        $this->assertSame(5, Product::count());
    }

    public function test_relancer_le_seeder_ne_cree_pas_de_doublon(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(1, Restaurant::count());
        $this->assertSame(4, User::count());
    }

    public function test_le_seeder_refuse_de_tourner_en_production(): void
    {
        $this->app['env'] = 'production';

        $this->app->make(DemoSeeder::class)->run();

        $this->assertSame(0, Restaurant::count());
        $this->assertSame(0, User::count());
    }
}
