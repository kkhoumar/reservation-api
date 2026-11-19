<?php

namespace Tests\Feature;

use App\Actions\StockService;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Dashboard;
use App\Livewire\NewOrder;
use App\Livewire\ProductsManage;
use App\Livewire\StockManage;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stock tracé (livraisons, pertes, inventaire) et vrai bénéfice
 * (prix d'achat figé à chaque vente).
 */
class StockTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $mariam;
    private User $jean;
    private Category $boissons;
    private Product $biere;
    private Product $sucrerie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->mariam = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Mariam']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons']);
        $this->biere = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id,
            'name' => 'Bière', 'selling_price' => 1000, 'cost_price' => 600, 'stock_quantity' => 20,
        ]);
        $this->sucrerie = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id,
            'name' => 'Sucrerie', 'selling_price' => 500, 'cost_price' => null, 'stock_quantity' => 10,
        ]);

        $this->actingAs($this->mariam);
    }

    private function stock(): StockService
    {
        return app(StockService::class);
    }

    // ---------- Produits ----------

    public function test_le_stock_de_depart_est_trace_a_la_creation(): void
    {
        Livewire::test(ProductsManage::class)
            ->call('openCreateForm')
            ->set('name', 'Coca')
            ->set('categoryId', $this->boissons->id)
            ->set('sellingPrice', '700')
            ->set('costPrice', '400')
            ->set('stockQuantity', '24')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $coca = Product::where('name', 'Coca')->sole();
        $this->assertSame(400, $coca->cost_price);
        $movement = StockMovement::where('product_id', $coca->id)->sole();
        $this->assertSame(['initial', 24, 0, 24], [$movement->type, $movement->quantity, $movement->stock_before, $movement->stock_after]);
    }

    public function test_modifier_un_produit_ne_change_jamais_le_stock(): void
    {
        Livewire::test(ProductsManage::class)
            ->call('editProduct', $this->biere->id)
            ->set('stockQuantity', '999')
            ->set('sellingPrice', '1100')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $biere = $this->biere->fresh();
        $this->assertSame(1100, $biere->selling_price);
        $this->assertSame(20, $biere->stock_quantity);
    }

    // ---------- Livraison ----------

    public function test_une_livraison_augmente_le_stock_et_met_a_jour_le_prix_d_achat(): void
    {
        $result = $this->stock()->recordDelivery($this->mariam, [
            $this->biere->id => ['quantity' => 24, 'unit_cost' => 650],
            $this->sucrerie->id => ['quantity' => 12, 'unit_cost' => 300],
        ], 'Solibra');

        $this->assertSame(44, $this->biere->fresh()->stock_quantity);
        $this->assertSame(650, $this->biere->fresh()->cost_price);
        $this->assertSame(300, $this->sucrerie->fresh()->cost_price);
        $this->assertSame(24 * 650 + 12 * 300, $result['total_cost']);
        $this->assertSame(2, StockMovement::where('type', 'delivery')->where('supplier', 'Solibra')->count());
        $this->assertNull($result['expense']);
    }

    public function test_livraison_payee_avec_la_caisse_cree_la_depense(): void
    {
        $caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id, 'opened_by' => $this->jean->id,
            'opening_amount' => 50000, 'opened_at' => now()->subHour(),
        ]);

        $result = $this->stock()->recordDelivery($this->mariam, [$this->biere->id => ['quantity' => 10, 'unit_cost' => 600]], 'Dépôt', true);

        $expense = $result['expense'];
        $this->assertSame(6000, $expense->amount);
        $this->assertSame('goods_purchase', $expense->category);
        $this->assertSame($caisse->id, $expense->cash_register_id);
        $this->assertSame(44000, $caisse->calculateTheoreticalAmount());
        $this->assertSame($expense->id, StockMovement::where('type', 'delivery')->sole()->expense_id);
    }

    public function test_livraison_payee_avec_la_caisse_mais_caisse_fermee_ne_change_rien(): void
    {
        try {
            $this->stock()->recordDelivery($this->mariam, [$this->biere->id => ['quantity' => 10, 'unit_cost' => 600]], null, true);
            $this->fail('Livraison acceptée sans caisse ouverte.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('Ouvrez la caisse', $e->getMessage());
        }

        // Tout ou rien : le stock n'a pas bougé
        $this->assertSame(20, $this->biere->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_ecran_livraison(): void
    {
        Livewire::test(StockManage::class)
            ->call('setTab', 'delivery')
            ->assertSet("deliveryLines.{$this->biere->id}.unit_cost", '600')
            ->set("deliveryLines.{$this->biere->id}.quantity", '12')
            ->set('supplier', 'Solibra')
            ->call('saveDelivery')
            ->assertHasNoErrors()
            ->assertSee('Livraison enregistrée');

        $this->assertSame(32, $this->biere->fresh()->stock_quantity);
    }

    // ---------- Perte ----------

    public function test_une_perte_baisse_le_stock_avec_un_motif(): void
    {
        $movement = $this->stock()->recordLoss($this->mariam, $this->biere->id, 2, 'Casse');

        $this->assertSame(18, $this->biere->fresh()->stock_quantity);
        $this->assertSame([-2, 600, 'Casse'], [$movement->quantity, $movement->unit_cost, $movement->reason]);
    }

    public function test_une_perte_sans_motif_ou_superieure_au_stock_est_refusee(): void
    {
        foreach ([[2, ''], [50, 'Casse']] as [$qty, $reason]) {
            try {
                $this->stock()->recordLoss($this->mariam, $this->biere->id, $qty, $reason);
                $this->fail("Perte acceptée : $qty / « $reason ».");
            } catch (BusinessRuleException) {
            }
        }

        $this->assertSame(20, $this->biere->fresh()->stock_quantity);
    }

    // ---------- Inventaire ----------

    public function test_l_inventaire_corrige_le_stock_et_chiffre_le_manquant(): void
    {
        // Bière : 20 attendues, 17 comptées → manque 3 × 600 ; sucrerie non comptée
        $count = $this->stock()->recordInventory($this->mariam, [$this->biere->id => '17', $this->sucrerie->id => '']);

        $this->assertSame(17, $this->biere->fresh()->stock_quantity);
        $this->assertSame(10, $this->sucrerie->fresh()->stock_quantity);
        $this->assertSame([1, -3, -1800], [$count->products_counted, $count->quantity_gap, $count->value_gap]);

        $m = StockMovement::where('type', 'inventory')->sole();
        $this->assertSame([20, 17, -3], [$m->stock_before, $m->stock_after, $m->quantity]);
    }

    public function test_l_inventaire_sans_prix_d_achat_chiffre_au_prix_de_vente(): void
    {
        $count = $this->stock()->recordInventory($this->mariam, [$this->sucrerie->id => 8]);

        $this->assertSame(-1000, $count->value_gap);
    }

    public function test_ecran_inventaire_a_l_aveugle_puis_resultat(): void
    {
        Livewire::test(StockManage::class)
            ->call('setTab', 'inventory')
            ->assertDontSee('stock 20')
            ->set("counts.{$this->biere->id}", '18')
            ->call('saveInventory')
            ->assertHasNoErrors()
            ->assertSee("Il manque l'équivalent de 1 200 FCFA", false)
            ->assertSee('attendu 20, compté 18');

        $this->assertSame(1, StockCount::count());
    }

    public function test_une_quantite_comptee_negative_est_refusee(): void
    {
        Livewire::test(StockManage::class)
            ->set("counts.{$this->biere->id}", '-4')
            ->call('saveInventory')
            ->assertHasErrors("counts.{$this->biere->id}");

        $this->assertSame(20, $this->biere->fresh()->stock_quantity);
    }

    // ---------- Droits et isolation ----------

    public function test_la_caissiere_ne_gere_pas_le_stock(): void
    {
        $this->actingAs($this->jean)->get(route('stock.index'))->assertRedirect(route('tables.index'));

        $this->expectException(BusinessRuleException::class);
        $this->stock()->recordLoss($this->jean, $this->biere->id, 1, 'Casse');
    }

    public function test_impossible_de_toucher_le_stock_d_un_autre_maquis(): void
    {
        $autre = Product::factory()->create(['stock_quantity' => 10]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->stock()->recordLoss($this->mariam, $autre->id, 1, 'Casse');
    }

    public function test_l_historique_liste_les_mouvements(): void
    {
        $this->stock()->recordLoss($this->mariam, $this->biere->id, 1, 'Périmé');

        Livewire::test(StockManage::class)
            ->call('setTab', 'history')
            ->assertSee('Perte / casse')
            ->assertSee('Périmé')
            ->assertSee('par Mariam');
    }

    // ---------- Vrai bénéfice ----------

    public function test_la_vente_fige_le_prix_d_achat(): void
    {
        $this->actingAs($this->jean);

        Livewire::test(NewOrder::class)
            ->set('quantity', 2)
            ->call('addProduct', $this->biere->id);

        $this->assertSame(600, OrderItem::sole()->unit_cost);

        // Un nouveau prix d'achat ne change pas les ventes passées
        $this->biere->update(['cost_price' => 900]);
        $this->assertSame(600, OrderItem::sole()->fresh()->unit_cost);
    }

    public function test_le_tableau_de_bord_affiche_le_vrai_benefice(): void
    {
        // 5 bières vendues 1 000 (achat 600) + 2 sucreries 500 (achat inconnu)
        $order = Order::factory()->awaitingPayment(6000)->create(['restaurant_id' => $this->maquis->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $this->biere->id, 'quantity' => 5, 'unit_price' => 1000, 'unit_cost' => 600, 'total' => 5000]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $this->sucrerie->id, 'quantity' => 2, 'unit_price' => 500, 'unit_cost' => null, 'total' => 1000]);
        Payment::recordCashPayment($order, $this->jean, 6000);

        Expense::factory()->create(['restaurant_id' => $this->maquis->id, 'category' => 'electricity', 'amount' => 500, 'expense_date' => now()]);
        Expense::factory()->create(['restaurant_id' => $this->maquis->id, 'category' => 'goods_purchase', 'amount' => 9999, 'expense_date' => now()]);
        $this->stock()->recordLoss($this->mariam, $this->biere->id, 1, 'Casse'); // 600

        $this->actingAs($this->patron);

        // 6 000 − 3 000 (coût) − 500 (électricité ; achats de marchandises exclus) − 600 (casse)
        Livewire::test(Dashboard::class)
            ->assertViewHas('costOfGoods', 3000)
            ->assertViewHas('profit', 1900)
            ->assertViewHas('stockLosses', 600)
            ->assertViewHas('uncostedQuantity', 2)
            ->assertSee('Bénéfice estimé')
            ->assertSee('2 articles vendus sans prix d', false);
    }
}
