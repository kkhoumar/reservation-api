<?php

namespace Tests\Feature;

use App\Actions\RestaurantDataManager;
use App\Actions\StockService;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Admin\RestaurantSubscription;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin : vider les données de test d'un maquis, ou le supprimer.
 */
class RestaurantDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Restaurant $maquis;
    private Restaurant $voisin;
    private Product $biere;
    private RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['restaurant_id' => null]);
        $this->admin->forceFill(['is_platform_admin' => true])->save();

        $this->maquis = Restaurant::factory()->create(['name' => 'Maquis Test', 'credit_enabled' => true]);
        $this->voisin = Restaurant::factory()->create(['name' => 'Maquis Voisin']);

        $this->remplir($this->maquis);
        $this->remplir($this->voisin);

        $this->biere = Product::where('restaurant_id', $this->maquis->id)->first();
        $this->table = RestaurantTable::where('restaurant_id', $this->maquis->id)->first();
    }

    /** Un maquis avec de l'activité de test partout. */
    private function remplir(Restaurant $r): void
    {
        $patron = User::factory()->owner()->create(['restaurant_id' => $r->id]);
        $caissier = User::factory()->cashier()->create(['restaurant_id' => $r->id]);
        $categorie = Category::factory()->create(['restaurant_id' => $r->id]);
        $biere = Product::factory()->create(['restaurant_id' => $r->id, 'category_id' => $categorie->id, 'stock_quantity' => 50, 'cost_price' => 600]);
        $table = RestaurantTable::factory()->create(['restaurant_id' => $r->id, 'status' => 'occupied']);

        CashRegister::factory()->create(['restaurant_id' => $r->id, 'opened_by' => $caissier->id, 'opening_amount' => 0, 'opened_at' => now()->subHour()]);

        $order = Order::factory()->awaitingPayment(2000)->create(['restaurant_id' => $r->id, 'table_id' => $table->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $biere->id, 'quantity' => 2, 'unit_price' => 1000, 'total' => 2000]);
        Payment::recordCashPayment($order, $caissier, 2000);

        Expense::factory()->create(['restaurant_id' => $r->id, 'expense_date' => now()]);
        $client = Customer::factory()->create(['restaurant_id' => $r->id]);
        $r->update(['credit_enabled' => true]);
        CreditTransaction::chargeOrder(Order::factory()->awaitingPayment(1000)->create(['restaurant_id' => $r->id]), $client, $patron);

        $this->actingAs($patron);
        app(StockService::class)->recordLoss($patron, $biere->id, 1, 'Casse');
        auth()->logout();
    }

    private function lignes(string $table, Restaurant $r): int
    {
        return DB::table($table)->where('restaurant_id', $r->id)->count();
    }

    // ---------- Vider les données de test ----------

    public function test_vider_efface_l_activite_et_garde_l_installation(): void
    {
        $equipe = $this->lignes('users', $this->maquis);
        $counts = app(RestaurantDataManager::class)->resetTestData($this->maquis, $this->admin);

        $this->assertSame(2, $counts['commandes']);
        foreach (['orders', 'cash_registers', 'expenses', 'credit_transactions', 'stock_movements'] as $t) {
            $this->assertSame(0, $this->lignes($t, $this->maquis), "Il reste des lignes dans $t");
        }
        $this->assertSame(0, DB::table('payments')->count() - DB::table('payments')->whereIn('order_id', DB::table('orders')->where('restaurant_id', $this->voisin->id)->pluck('id'))->count());

        // L'installation est conservée
        $this->assertSame($equipe, $this->lignes('users', $this->maquis));
        $this->assertSame(1, $this->lignes('products', $this->maquis));
        $this->assertSame(1, $this->lignes('restaurant_tables', $this->maquis));
        $this->assertSame(1, $this->lignes('customers', $this->maquis));
        $this->assertSame('free', $this->table->fresh()->status);
        $this->assertSame(49, DB::table('products')->where('id', $this->biere->id)->value('stock_quantity'));
    }

    public function test_options_clients_et_stock_a_zero(): void
    {
        app(RestaurantDataManager::class)->resetTestData($this->maquis, $this->admin, deleteCustomers: true, zeroStock: true);

        $this->assertSame(0, $this->lignes('customers', $this->maquis));
        $this->assertSame(0, DB::table('products')->where('id', $this->biere->id)->value('stock_quantity'));
    }

    public function test_le_maquis_voisin_n_est_jamais_touche(): void
    {
        app(RestaurantDataManager::class)->resetTestData($this->maquis, $this->admin, true, true);

        $this->assertSame(2, $this->lignes('orders', $this->voisin));
        $this->assertSame(1, $this->lignes('cash_registers', $this->voisin));
        $this->assertSame(1, $this->lignes('customers', $this->voisin));
        $this->assertSame(1, $this->lignes('stock_movements', $this->voisin));
    }

    public function test_ecran_vider_exige_le_nom_exact(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RestaurantSubscription::class, ['restaurant' => $this->maquis])
            ->assertSee('Zone dangereuse')
            ->set('resetConfirm', 'Maquis Autre')
            ->call('resetData')
            ->assertHasErrors('resetConfirm');

        $this->assertSame(2, $this->lignes('orders', $this->maquis));

        Livewire::test(RestaurantSubscription::class, ['restaurant' => $this->maquis])
            ->set('resetConfirm', '  maquis test ')
            ->call('resetData')
            ->assertHasNoErrors()
            ->assertSee('Données de test vidées');

        $this->assertSame(0, $this->lignes('orders', $this->maquis));
    }

    // ---------- Supprimer le maquis ----------

    public function test_supprimer_efface_tout_le_maquis(): void
    {
        $equipeVoisin = $this->lignes('users', $this->voisin);
        $this->actingAs($this->admin);
        session([User::ADMIN_RESTAURANT_SESSION_KEY => $this->maquis->id]);

        Livewire::test(RestaurantSubscription::class, ['restaurant' => $this->maquis])
            ->set('deleteConfirm', 'Maquis Test')
            ->call('deleteRestaurant')
            ->assertRedirect(route('admin.restaurants'));

        $this->assertNull(Restaurant::find($this->maquis->id));
        foreach (['users', 'products', 'orders', 'cash_registers', 'customers', 'stock_movements'] as $t) {
            $this->assertSame(0, $this->lignes($t, $this->maquis), "Il reste des lignes dans $t");
        }
        $this->assertNull(session(User::ADMIN_RESTAURANT_SESSION_KEY));

        // Le voisin et le Super Admin sont intacts
        $this->assertSame($equipeVoisin, $this->lignes('users', $this->voisin));
        $this->assertNotNull($this->admin->fresh());
    }

    public function test_supprimer_exige_le_nom_exact(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RestaurantSubscription::class, ['restaurant' => $this->maquis])
            ->set('deleteConfirm', 'oui')
            ->call('deleteRestaurant')
            ->assertHasErrors('deleteConfirm');

        $this->assertNotNull(Restaurant::find($this->maquis->id));
    }

    public function test_reserve_au_super_admin(): void
    {
        $patron = User::where('restaurant_id', $this->maquis->id)->where('role', 'owner')->first();

        $this->actingAs($patron)
            ->get(route('admin.restaurants.subscription', $this->maquis->id))
            ->assertRedirect(route('tables.index'));

        $this->expectException(BusinessRuleException::class);
        app(RestaurantDataManager::class)->deleteRestaurant($this->maquis, $patron);
    }
}
