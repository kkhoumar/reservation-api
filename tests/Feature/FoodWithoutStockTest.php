<?php

namespace Tests\Feature;

use App\Livewire\NewOrder;
use App\Livewire\ProductsManage;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\PeriodStats;
use App\Support\RegisterReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Un plat ne se compte pas comme une bouteille : sans suivi de stock, il ne
 * tombe jamais « épuisé » tout seul ; la cuisine le marque « Fini pour
 * aujourd'hui ». Le bilan sépare la nourriture et les boissons.
 */
class FoodWithoutStockTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private Category $plats;
    private Category $boissons;
    private Product $garba;
    private Product $flag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create(['service_mode' => 'both', 'payment_timing' => 'after', 'name' => 'Chez Koffi']);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->plats = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Plats', 'kind' => 'food']);
        $this->boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons', 'kind' => 'drink']);
        $this->garba = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->plats->id,
            'name' => 'Garba', 'selling_price' => 2000, 'cost_price' => 1200, 'stock_quantity' => 0, 'track_stock' => false]);
        $this->flag = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id,
            'name' => 'Flag', 'selling_price' => 1000, 'cost_price' => 600, 'stock_quantity' => 10]);
        $this->actingAs($this->patron);
    }

    public function test_un_plat_sans_stock_se_vend_sans_limite_et_sans_toucher_au_stock(): void
    {
        Livewire::test(NewOrder::class)->call('addProduct', $this->garba->id, 30, 't1');

        $this->assertSame(30, (int) OrderItem::sole()->quantity);
        $this->assertSame(0, $this->garba->fresh()->stock_quantity);
    }

    public function test_une_boisson_suivie_reste_bloquee_par_son_stock(): void
    {
        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id, 11, 't1');

        $this->assertSame(0, OrderItem::count());
        $this->assertSame(10, $this->flag->fresh()->stock_quantity);
    }

    public function test_fini_pour_aujourd_hui_bloque_jusqu_a_demain(): void
    {
        Livewire::test(ProductsManage::class)->call('toggleSoldOut', $this->garba->id)->assertSee("Fini aujourd'hui", false);

        Livewire::test(NewOrder::class)->call('addProduct', $this->garba->id);
        $this->assertSame(0, OrderItem::count());

        // Le lendemain, il revient tout seul
        $this->travel(1)->days();
        Livewire::test(NewOrder::class)->call('addProduct', $this->garba->id);
        $this->assertSame(1, OrderItem::count());
    }

    public function test_un_nouveau_plat_ne_demande_pas_de_stock(): void
    {
        Livewire::test(ProductsManage::class)
            ->call('openCreateForm')
            ->set('name', 'Attiéké poisson')
            ->set('categoryId', $this->plats->id)
            ->assertSet('trackStock', false)
            ->set('sellingPrice', '3000')
            ->set('costPrice', '1800')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $plat = Product::where('name', 'Attiéké poisson')->sole();
        $this->assertFalse($plat->track_stock);

        // Une boisson le demande
        Livewire::test(ProductsManage::class)
            ->call('openCreateForm')
            ->set('categoryId', $this->boissons->id)
            ->assertSet('trackStock', true);
    }

    public function test_ranger_une_categorie_dans_nourriture_arrete_le_suivi_du_stock(): void
    {
        $poulet = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id, 'name' => 'Poulet', 'stock_quantity' => 0]);

        Livewire::test(ProductsManage::class)->call('setCategoryKind', $this->boissons->id, 'food');

        $this->assertFalse($poulet->fresh()->track_stock);
    }

    public function test_un_plat_sans_stock_n_apparait_pas_au_stock(): void
    {
        $this->get(route('stock.index'))->assertOk()->assertSee('Flag')->assertDontSee('Garba');
    }

    public function test_le_bilan_separe_nourriture_et_boissons(): void
    {
        $caissier = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $caisse = CashRegister::factory()->create(['restaurant_id' => $this->maquis->id, 'opened_by' => $caissier->id, 'opening_amount' => 0, 'opened_at' => now()->subHours(3)]);

        $order = Order::factory()->awaitingPayment(9000)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $caissier->id]);
        foreach ([[$this->garba, 3], [$this->flag, 3]] as [$p, $q]) {
            OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $p->id, 'quantity' => $q,
                'unit_price' => $p->selling_price, 'unit_cost' => $p->cost_price, 'total' => $p->selling_price * $q]);
        }
        Payment::recordCashPayment($order, $caissier, 9000);

        $stats = new PeriodStats($this->maquis->id, now()->subDay(), now()->addMinute());
        $food = $stats->byKind['food'];
        $drink = $stats->byKind['drink'];
        $this->assertSame([6000, 3, 2400], [$food->amount, $food->quantity, $food->margin]);
        $this->assertSame([3000, 3, 1200], [$drink->amount, $drink->quantity, $drink->margin]);
        $this->assertSame('Garba', $food->items->first()->name);

        $caisse->close(9000, $caissier->id);
        $text = (new RegisterReport($caisse->fresh()))->toWhatsappText();
        $this->assertStringContainsString('*Nourriture : 6 000 FCFA* (3) · bénéfice 2 400', $text);
        $this->assertStringContainsString('• Garba ×3 · 6 000', $text);
        $this->assertStringContainsString('*Boissons : 3 000 FCFA* (3)', $text);
    }
}
