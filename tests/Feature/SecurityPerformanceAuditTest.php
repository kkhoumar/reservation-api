<?php

namespace Tests\Feature;

use App\Livewire\Login;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\SalesSummary;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit sécurité et performance (plusieurs maquis, beaucoup de monde).
 */
class SecurityPerformanceAuditTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Comptes de démo ----------

    public function test_en_ligne_la_demo_utilise_demo_password_et_jamais_password(): void
    {
        $this->app['env'] = 'production';
        config(['app.demo_password' => 'Mon-Maquis-2026']);

        $this->app->make(DemoSeeder::class)->run();

        $koffi = User::where('phone', User::normalizePhone('0711111111'))->sole();
        $this->assertTrue(Hash::check('Mon-Maquis-2026', $koffi->password));
        $this->assertFalse(Hash::check('password', $koffi->password));
    }

    public function test_en_ligne_demo_password_trop_court_ou_password_est_refuse(): void
    {
        $this->app['env'] = 'staging';

        foreach ([null, 'court', 'password'] as $bad) {
            config(['app.demo_password' => $bad]);
            $this->app->make(DemoSeeder::class)->run();
        }

        $this->assertSame(0, Restaurant::count());
    }

    public function test_au_demarrage_les_anciens_comptes_de_demo_perdent_password(): void
    {
        $this->seed(DemoSeeder::class); // créés avec « password » (comme sur Render avant)
        $this->app['env'] = 'production';
        config(['app.demo_password' => null]);

        $this->artisan('app:secure-demo')->assertSuccessful();

        foreach (DemoSeeder::ACCOUNTS as [, , $phone]) {
            $user = User::where('phone', User::normalizePhone($phone))->sole();
            $this->assertFalse(Hash::check('password', $user->password), $phone);
        }
    }

    public function test_au_demarrage_les_comptes_de_demo_prennent_demo_password(): void
    {
        $this->seed(DemoSeeder::class);
        $this->app['env'] = 'production';
        config(['app.demo_password' => 'Mon-Maquis-2026']);

        $this->artisan('app:secure-demo')->assertSuccessful();

        $aya = User::where('phone', User::normalizePhone('0722222222'))->sole();
        $this->assertTrue(Hash::check('Mon-Maquis-2026', $aya->password));
    }

    public function test_les_vrais_comptes_ne_sont_jamais_touches(): void
    {
        $maquis = Restaurant::factory()->create();
        $patron = User::factory()->owner()->create(['restaurant_id' => $maquis->id, 'password' => 'password']);
        $this->app['env'] = 'production';

        $this->artisan('app:secure-demo')->assertSuccessful();

        $this->assertTrue(Hash::check('password', $patron->fresh()->password));
    }

    // ---------- Connexion ----------

    public function test_changer_de_connexion_internet_ne_permet_pas_d_essayer_sans_fin(): void
    {
        $maquis = Restaurant::factory()->create();
        $patron = User::factory()->owner()->create(['restaurant_id' => $maquis->id, 'phone' => '0799999999', 'password' => 'Bon-Mot-De-Passe']);

        // 20 essais ratés venus de 20 connexions différentes
        for ($i = 1; $i <= 20; $i++) {
            $this->app['request']->server->set('REMOTE_ADDR', "10.0.0.{$i}");
            Livewire::test(Login::class)->set('phone', '0799999999')->set('password', 'mauvais')->call('authenticate');
        }

        // Même le bon mot de passe, depuis une nouvelle connexion, est bloqué pour l'instant
        $this->app['request']->server->set('REMOTE_ADDR', '10.0.1.1');
        Livewire::test(Login::class)->set('phone', '0799999999')->set('password', 'Bon-Mot-De-Passe')->call('authenticate')
            ->assertHasErrors('phone')
            ->assertSee('Trop de tentatives');
        $this->assertGuest();

        RateLimiter::clear('login-phone:' . User::normalizePhone('0799999999'));
    }

    // ---------- Performance ----------

    public function test_le_graphique_des_7_jours_donne_les_memes_chiffres(): void
    {
        $maquis = Restaurant::factory()->create();
        $patron = User::factory()->owner()->create(['restaurant_id' => $maquis->id]);
        $this->actingAs($patron);

        $order = Order::factory()->create(['restaurant_id' => $maquis->id, 'user_id' => $patron->id]);
        $pay = fn ($when, $due, $mobile) => Payment::factory()->create([
            'order_id' => $order->id, 'status' => 'completed', 'paid_at' => $when,
            'amount_due' => $due, 'mobile_amount' => $mobile, 'payment_method' => $mobile > 0 ? 'wave' : 'cash',
        ]);
        $pay(now()->subDays(2)->setTime(12, 0), 5000, 0);
        $pay(now()->subDays(2)->setTime(20, 0), 3000, 3000);
        $pay(now()->setTime(1, 0), 1200, 500);
        $client = Customer::factory()->create(['restaurant_id' => $maquis->id]);
        $credit = new CreditTransaction();
        $credit->forceFill(['restaurant_id' => $maquis->id, 'customer_id' => $client->id, 'user_id' => $patron->id, 'type' => 'charge', 'amount' => 700, 'created_at' => now()->subDays(2)])->save();

        $byDay = SalesSummary::totalsByDay($maquis->id, now()->subDays(6)->startOfDay(), now()->endOfDay());

        foreach (range(6, 0) as $daysAgo) {
            $day = now()->subDays($daysAgo);
            $old = (new SalesSummary($maquis->id, $day->copy()->startOfDay(), $day->copy()->endOfDay()))->total();
            $this->assertSame($old, $byDay[$day->toDateString()] ?? 0, $day->toDateString());
        }
        $this->assertSame(8700, $byDay[now()->subDays(2)->toDateString()]);
    }

    public function test_les_index_de_vitesse_existent(): void
    {
        $this->assertTrue(Schema::hasIndex('order_items', 'perf_order_items_order_id'));
        $this->assertTrue(Schema::hasIndex('payments', 'perf_payments_order_id'));
        $this->assertTrue(Schema::hasIndex('orders', 'perf_orders_restaurant_id_status_created_at'));
    }
}
