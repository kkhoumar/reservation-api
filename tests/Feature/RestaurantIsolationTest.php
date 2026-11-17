<?php

namespace Tests\Feature;

use App\Livewire\NewOrder;
use App\Livewire\ProductsManage;
use App\Livewire\SalesToday;
use App\Livewire\TakePayment;
use App\Livewire\UsersManage;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Isolation entre maquis : un utilisateur du maquis A ne doit JAMAIS
 * voir ni modifier les données du maquis B.
 */
class RestaurantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;
    private Order $orderA;

    private User $ownerB;
    private RestaurantTable $tableB;
    private Category $categoryB;
    private Product $productB;
    private Order $orderB;
    private OrderItem $itemB;
    private Payment $paymentB;
    private CashRegister $registerB;

    protected function setUp(): void
    {
        parent::setUp();

        // Maquis A
        $restaurantA = Restaurant::factory()->create(['name' => 'Maquis A']);
        $this->ownerA = User::factory()->owner()->create(['restaurant_id' => $restaurantA->id]);
        $this->orderA = Order::factory()->awaitingPayment()->create(['restaurant_id' => $restaurantA->id, 'order_number' => 'CMD-A-0001']);

        // Maquis B, avec des données de chaque type
        $restaurantB = Restaurant::factory()->create(['name' => 'Maquis B']);
        $this->ownerB = User::factory()->owner()->create(['restaurant_id' => $restaurantB->id]);
        $zoneB = Zone::factory()->create(['restaurant_id' => $restaurantB->id]);
        $this->tableB = RestaurantTable::factory()->create(['restaurant_id' => $restaurantB->id, 'zone_id' => $zoneB->id]);
        $this->categoryB = Category::factory()->create(['restaurant_id' => $restaurantB->id]);
        $this->productB = Product::factory()->create(['restaurant_id' => $restaurantB->id, 'category_id' => $this->categoryB->id]);
        $this->orderB = Order::factory()->awaitingPayment()->create(['restaurant_id' => $restaurantB->id, 'order_number' => 'CMD-B-0001']);
        $this->itemB = OrderItem::factory()->create(['order_id' => $this->orderB->id, 'product_id' => $this->productB->id]);
        $this->paymentB = Payment::factory()->create(['order_id' => $this->orderB->id]);
        $this->registerB = CashRegister::factory()->closed()->create(['restaurant_id' => $restaurantB->id]);
        Expense::factory()->create(['restaurant_id' => $restaurantB->id]);
        Customer::factory()->create(['restaurant_id' => $restaurantB->id]);
    }

    // ---------------------------------------------------------------
    // Filtre automatique sur les modèles
    // ---------------------------------------------------------------

    public function test_les_requetes_ne_renvoient_que_les_donnees_du_maquis_connecte(): void
    {
        $this->actingAs($this->ownerA);

        foreach ([Zone::class, RestaurantTable::class, Category::class, Product::class, CashRegister::class, Expense::class, Customer::class, OrderItem::class, Payment::class] as $model) {
            $this->assertSame(0, $model::count(), "$model : des données du maquis B sont visibles");
        }

        $this->assertSame([$this->orderA->id], Order::pluck('id')->all());
        $this->assertNull(Order::find($this->orderB->id));
        $this->assertNull(Product::find($this->productB->id));
        $this->assertNull(OrderItem::find($this->itemB->id));
    }

    public function test_le_maquis_b_voit_ses_propres_donnees(): void
    {
        $this->actingAs($this->ownerB);

        $this->assertNotNull(Order::find($this->orderB->id));
        $this->assertNull(Order::find($this->orderA->id));
        $this->assertSame(1, Product::count());
        $this->assertSame(1, Payment::count());
    }

    public function test_un_utilisateur_sans_restaurant_ne_voit_aucune_donnee_de_maquis(): void
    {
        $sansRestaurant = User::factory()->create(['restaurant_id' => null]);
        $this->actingAs($sansRestaurant);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Payment::count());
    }

    public function test_sans_utilisateur_connecte_aucun_filtre_n_est_applique(): void
    {
        // Console, files d'attente, seeders : accès à tout
        $this->assertSame(2, Order::count());
    }

    public function test_le_restaurant_est_rempli_automatiquement_a_la_creation(): void
    {
        $this->actingAs($this->ownerA);

        $category = Category::create(['name' => 'Boissons']);

        $this->assertSame($this->ownerA->restaurant_id, $category->restaurant_id);
    }

    public function test_le_numero_de_commande_reste_unique_entre_maquis(): void
    {
        $this->actingAs($this->ownerA);

        $numero = Order::generateOrderNumber();

        $this->assertFalse(Order::withoutGlobalScopes()->where('order_number', $numero)->exists());
    }

    // ---------------------------------------------------------------
    // Pages : un ID du maquis B dans l'URL donne une 404
    // ---------------------------------------------------------------

    public function test_impossible_d_ouvrir_l_encaissement_d_une_commande_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('payment.take', $this->orderB->id))
            ->assertNotFound();
    }

    public function test_impossible_de_voir_le_recu_d_une_commande_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('payment.success', $this->orderB->id))
            ->assertNotFound();
    }

    public function test_impossible_de_voir_la_cloture_de_caisse_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('cash-register.closed', $this->registerB->id))
            ->assertNotFound();
    }

    public function test_impossible_de_prendre_commande_sur_la_table_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('orders.new', $this->tableB->id))
            ->assertNotFound();
    }

    public function test_le_maquis_a_accede_normalement_a_ses_propres_pages(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('payment.take', $this->orderA->id))
            ->assertOk();
    }

    // ---------------------------------------------------------------
    // Actions Livewire
    // ---------------------------------------------------------------

    public function test_impossible_d_ajouter_un_produit_d_un_autre_maquis_a_sa_commande(): void
    {
        $this->actingAs($this->ownerA);

        $this->assertBlocked(fn () => Livewire::test(NewOrder::class)
            ->call('addProduct', $this->productB->id));

        $this->assertSame(1, OrderItem::withoutGlobalScopes()->count());
        $this->assertSame(50, $this->productB->fresh()->stock_quantity);
    }

    public function test_impossible_de_supprimer_une_ligne_de_commande_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA);

        Livewire::test(NewOrder::class)
            ->call('removeItem', $this->itemB->id);

        $this->assertDatabaseHas('order_items', ['id' => $this->itemB->id]);
    }

    public function test_les_identifiants_sensibles_ne_peuvent_pas_etre_modifies_depuis_le_navigateur(): void
    {
        $this->actingAs($this->ownerA);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(TakePayment::class, ['order' => $this->orderA->id])
            ->set('orderId', $this->orderB->id);
    }

    public function test_impossible_de_creer_un_produit_dans_la_categorie_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA);

        Livewire::test(ProductsManage::class)
            ->set('name', 'Produit pirate')
            ->set('sellingPrice', '500')
            ->set('categoryId', $this->categoryB->id)
            ->set('stockQuantity', '10')
            ->call('saveProduct')
            ->assertHasErrors('categoryId');

        $this->assertDatabaseMissing('products', ['name' => 'Produit pirate']);
    }

    public function test_impossible_de_modifier_un_produit_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA);

        $this->assertBlocked(fn () => Livewire::test(ProductsManage::class)
            ->call('toggleAvailability', $this->productB->id));

        $this->assertTrue($this->productB->fresh()->is_available);
    }

    public function test_impossible_de_modifier_un_utilisateur_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA);

        $this->assertBlocked(fn () => Livewire::test(UsersManage::class)
            ->call('toggleStatus', $this->ownerB->id));

        $this->assertSame('active', $this->ownerB->fresh()->status);
    }

    /**
     * L'action doit être refusée : la donnée de l'autre maquis est
     * introuvable (ModelNotFoundException, transformée en 404 en production).
     */
    private function assertBlocked(callable $action): void
    {
        try {
            $action();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("L'action sur une donnée d'un autre maquis n'a pas été bloquée.");
    }

    public function test_les_ventes_du_jour_n_affichent_pas_celles_d_un_autre_maquis(): void
    {
        $this->actingAs($this->ownerA);

        Livewire::test(SalesToday::class)
            ->assertDontSee('CMD-B-0001');
    }
}
