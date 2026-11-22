<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Vente de secours : faite au comptoir sans internet, envoyée au retour de la
 * connexion. Jamais comptée deux fois, toujours dans la caisse, toujours tracée.
 */
class OfflineSaleTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $jean;
    private CashRegister $caisse;
    private Product $flag;
    private Product $poulet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create(['name' => 'Chez Koffi']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->caisse = $this->openRegister($this->maquis, $this->jean, 10000);

        $boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons']);
        $plats = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Plats']);
        $this->flag = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $boissons->id, 'name' => 'Flag', 'selling_price' => 1000, 'cost_price' => 600, 'stock_quantity' => 24]);
        $this->poulet = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $plats->id, 'name' => 'Poulet braisé', 'selling_price' => 3500, 'cost_price' => 2000, 'stock_quantity' => 10]);
    }

    private function sale(array $overrides = []): array
    {
        return array_merge([
            'uuid' => (string) Str::uuid(),
            'sold_at' => now()->subMinutes(20)->toIso8601String(),
            'total' => 6500,
            'cash_received' => 10000,
            'items' => [
                ['product_id' => $this->flag->id, 'quantity' => 3],
                ['product_id' => $this->poulet->id, 'quantity' => 1],
            ],
        ], $overrides);
    }

    private function send(array ...$sales)
    {
        return $this->actingAs($this->jean)->postJson(route('offline.sync'), ['sales' => $sales]);
    }

    public function test_une_vente_de_secours_entre_dans_la_caisse_et_le_stock(): void
    {
        $sale = $this->sale();

        $this->send($sale)->assertOk()->assertJsonPath('results.0.status', 'saved');

        $order = Order::sole();
        $this->assertSame('paid', $order->status);
        $this->assertSame($sale['uuid'], $order->offline_uuid);
        $this->assertSame(6500, (int) $order->total);
        $this->assertNotNull($order->offline_sold_at);

        $payment = Payment::sole();
        $this->assertSame(['cash', 10000, 3500, 'completed'], [$payment->payment_method, $payment->amount_received, $payment->change_given, $payment->status]);

        // Le tiroir reçoit 6 500 F (10 000 reçus, 3 500 rendus)
        $this->assertSame(16500, $this->caisse->calculateTheoreticalAmount());
        $this->assertSame(21, $this->flag->fresh()->stock_quantity);
        $this->assertSame(9, $this->poulet->fresh()->stock_quantity);

        $log = ActivityLog::where('action', 'offline_sale')->sole();
        $this->assertStringContainsString('sans internet', $log->description);
        $this->assertStringContainsString('3 × Flag', $log->description);
        $this->assertSame('Jean', explode(' ', $log->user_name)[0]);
        $this->assertSame('info', $log->severity());
    }

    public function test_une_vente_envoyee_deux_fois_n_est_comptee_qu_une_fois(): void
    {
        $sale = $this->sale();

        $this->send($sale)->assertJsonPath('results.0.status', 'saved');
        $this->send($sale)->assertJsonPath('results.0.status', 'duplicate');
        $this->send($sale, $sale)->assertJsonPath('results.1.status', 'duplicate');

        $this->assertSame(1, Order::count());
        $this->assertSame(1, Payment::count());
        $this->assertSame(21, $this->flag->fresh()->stock_quantity);
    }

    public function test_caisse_fermee_la_vente_attend_dans_le_telephone(): void
    {
        $this->caisse->close(10000, $this->jean->id);

        $this->send($this->sale())
            ->assertOk()
            ->assertJsonPath('results.0.status', 'wait')
            ->assertJsonPath('results.0.message', 'La caisse est fermée. Ouvrez la caisse pour envoyer les ventes de secours.');

        $this->assertSame(0, Order::count());
        $this->assertSame(24, $this->flag->fresh()->stock_quantity);
    }

    public function test_prix_change_entre_temps_la_vente_est_gardee_et_signalee(): void
    {
        $this->flag->update(['selling_price' => 1200]);

        $this->send($this->sale())->assertJsonPath('results.0.status', 'saved');

        // Le client a payé 6 500 F au comptoir : c'est ce qui est entré dans le tiroir
        $this->assertSame(16500, $this->caisse->calculateTheoreticalAmount());
        $this->assertSame(7100, (int) Order::sole()->total);

        $log = ActivityLog::where('action', 'offline_sale')->sole();
        $this->assertStringContainsString('À vérifier', $log->description);
        $this->assertSame('warning', $log->severity());
    }

    public function test_stock_insuffisant_la_vente_est_gardee_et_signalee(): void
    {
        $this->poulet->update(['stock_quantity' => 0]);

        $this->send($this->sale())->assertJsonPath('results.0.status', 'saved');

        $this->assertSame(-1, $this->poulet->fresh()->stock_quantity);
        $this->assertStringContainsString('stock insuffisant pour Poulet braisé', ActivityLog::where('action', 'offline_sale')->value('description'));
    }

    public function test_ventes_invalides_refusees(): void
    {
        $autreMaquis = Restaurant::factory()->create();
        $produitAilleurs = Product::factory()->create(['restaurant_id' => $autreMaquis->id, 'category_id' => Category::factory()->create(['restaurant_id' => $autreMaquis->id])->id]);

        $this->send(
            $this->sale(['items' => [['product_id' => $produitAilleurs->id, 'quantity' => 1]], 'total' => 1000, 'cash_received' => 1000]),
            $this->sale(['sold_at' => now()->subDays(5)->toIso8601String()]),
            $this->sale(['cash_received' => 5000]),
            $this->sale(['uuid' => 'pas-un-numero']),
            $this->sale(['items' => []]),
        )
            ->assertOk()
            ->assertJsonPath('results.0.status', 'rejected')
            ->assertJsonPath('results.1.status', 'rejected')
            ->assertJsonPath('results.2.status', 'rejected')
            ->assertJsonPath('results.3.status', 'rejected')
            ->assertJsonPath('results.4.status', 'rejected');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_le_numero_d_une_vente_d_un_autre_maquis_est_refuse(): void
    {
        $sale = $this->sale();
        $this->send($sale)->assertJsonPath('results.0.status', 'saved');

        $autre = User::factory()->cashier()->create(['restaurant_id' => ($autreMaquis = Restaurant::factory()->create())->id]);
        $this->openRegister($autreMaquis, $autre);

        $this->actingAs($autre)->postJson(route('offline.sync'), ['sales' => [$sale]])
            ->assertJsonPath('results.0.status', 'rejected');
    }

    public function test_le_catalogue_contient_les_produits_du_maquis(): void
    {
        $autreMaquis = Restaurant::factory()->create();
        Product::factory()->create(['restaurant_id' => $autreMaquis->id, 'name' => 'Produit ailleurs', 'category_id' => Category::factory()->create(['restaurant_id' => $autreMaquis->id])->id]);
        $this->poulet->update(['is_available' => false]);

        $response = $this->actingAs($this->jean)->getJson(route('offline.catalogue'))
            ->assertOk()
            ->assertJsonPath('restaurant.id', $this->maquis->id)
            ->assertJsonPath('user.id', $this->jean->id)
            ->assertJsonPath('register_open', true);

        $names = collect($response->json('categories'))->pluck('products')->flatten(1)->pluck('name')->all();
        $this->assertSame(['Flag'], $names);
        $this->assertNotEmpty($response->json('csrf'));
    }

    public function test_la_page_de_secours_marche_pour_le_comptoir_seulement(): void
    {
        $this->actingAs($this->jean)->get(route('offline.page'))->assertOk()->assertSee('Vente de secours');

        $aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($aya)->get(route('offline.page'))->assertRedirect();
        $this->actingAs($aya)->getJson(route('offline.catalogue'))->assertStatus(302);
        $this->actingAs($aya)->postJson(route('offline.sync'), ['sales' => [$this->sale()]]);
        $this->assertSame(0, Order::count());
    }
}
