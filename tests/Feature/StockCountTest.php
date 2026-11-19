<?php

namespace Tests\Feature;

use App\Actions\StockService;
use App\Livewire\Dashboard;
use App\Livewire\ProductsManage;
use App\Livewire\StockCountPage;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\StockCountReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Comptage du stock : l'application calcule ce qui doit rester depuis le
 * dernier comptage, le patron compte, explique les écarts et valide.
 * Totaux séparés boissons / nourriture, bénéfice, prix d'achat obligatoire.
 */
class StockCountTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $mariam;
    private User $awa;
    private Category $boissons;
    private Category $plats;
    private Product $biere;
    private Product $coca;
    private Product $poulet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Koffi']);
        $this->mariam = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Mariam']);
        $this->awa = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Awa']);

        $this->boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons']);
        $this->plats = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Plats']);

        $this->biere = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id,
            'name' => 'Bière', 'selling_price' => 1000, 'cost_price' => 600, 'stock_quantity' => 20,
        ]);
        $this->coca = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id,
            'name' => 'Coca', 'selling_price' => 500, 'cost_price' => 300, 'stock_quantity' => 10,
        ]);
        $this->poulet = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $this->plats->id,
            'name' => 'Poulet', 'selling_price' => 3500, 'cost_price' => 2000, 'stock_quantity' => 5,
        ]);

        $this->actingAs($this->patron);
    }

    private function stock(): StockService
    {
        return app(StockService::class);
    }

    /** Bière −3 (disparue), Coca +1, Poulet juste. */
    private function simpleCount(): StockCount
    {
        return $this->stock()->recordInventory(
            $this->patron,
            [$this->biere->id => '17', $this->coca->id => '11', $this->poulet->id => '5'],
            null,
            [$this->biere->id => 'vol'],
        );
    }

    // ---------- Familles boissons / nourriture ----------

    public function test_la_famille_d_une_categorie_est_devinee_d_apres_son_nom(): void
    {
        foreach (['Boissons', 'BOISONS', 'Bières', 'BOCK 66', 'Eau minérale', 'Jus naturels', 'Sucreries'] as $name) {
            $this->assertSame('drink', Category::guessKind($name), $name);
        }

        foreach (['Plats', 'Grillades', 'Gâteaux', 'Aubergine', 'Morceaux de poulet'] as $name) {
            $this->assertSame('food', Category::guessKind($name), $name);
        }

        $this->assertSame('drink', $this->boissons->fresh()->kind);
        $this->assertSame('food', $this->plats->fresh()->kind);
    }

    public function test_le_patron_choisit_la_famille_d_une_categorie(): void
    {
        Livewire::test(ProductsManage::class)
            ->set('newCategoryName', 'Spécialités')
            ->set('newCategoryKind', 'drink')
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->call('setCategoryKind', $this->plats->id, 'drink');

        $this->assertSame('drink', Category::where('name', 'Spécialités')->sole()->kind);
        $this->assertSame('drink', $this->plats->fresh()->kind);
    }

    // ---------- Prix obligatoires ----------

    public function test_le_prix_d_achat_est_obligatoire_et_ne_depasse_pas_le_prix_de_vente(): void
    {
        $form = Livewire::test(ProductsManage::class)
            ->call('openCreateForm')
            ->set('name', 'Fanta')
            ->set('categoryId', $this->boissons->id)
            ->set('sellingPrice', '700')
            ->set('stockQuantity', '12')
            ->set('costPrice', '')
            ->call('saveProduct')
            ->assertHasErrors(['costPrice' => 'required'])
            ->set('costPrice', '900')
            ->call('saveProduct')
            ->assertHasErrors(['costPrice' => 'lte'])
            ->assertSee('vous perdriez de l&#039;argent', false)
            ->set('costPrice', '450')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $this->assertSame(450, Product::where('name', 'Fanta')->sole()->cost_price);
    }

    // ---------- Enregistrement du comptage ----------

    public function test_le_comptage_garde_la_photo_de_chaque_produit_depuis_le_comptage_precedent(): void
    {
        $first = $this->stock()->recordInventory($this->mariam, [$this->biere->id => '20', $this->poulet->id => '5']);

        $this->travel(3)->days();

        // Depuis : +24 livrées, −30 vendues, −1 cassée → il doit rester 13
        $this->stock()->recordDelivery($this->mariam, [$this->biere->id => ['quantity' => 24, 'unit_cost' => 600]]);
        $order = Order::factory()->awaitingPayment(30000)->create(['restaurant_id' => $this->maquis->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $this->biere->id, 'quantity' => 30, 'unit_price' => 1000, 'unit_cost' => 600, 'total' => 30000]);
        $this->biere->decrementStock(30);
        $this->stock()->recordLoss($this->mariam, $this->biere->id, 1, 'Casse');

        // Une commande annulée ne compte pas comme vendue
        $cancelled = Order::factory()->create(['restaurant_id' => $this->maquis->id, 'status' => 'cancelled']);
        OrderItem::factory()->create(['order_id' => $cancelled->id, 'product_id' => $this->biere->id, 'quantity' => 5, 'unit_price' => 1000, 'total' => 5000]);

        $this->assertSame(13, $this->biere->fresh()->stock_quantity);

        $count = $this->stock()->recordInventory(
            $this->patron,
            [$this->biere->id => '10', $this->coca->id => '11', $this->poulet->id => '5'],
            'Casier rendu',
            [$this->biere->id => 'vol', $this->coca->id => 'n-importe-quoi', $this->poulet->id => 'casse'],
        );

        $this->assertSame($first->created_at->toDateTimeString(), $count->previous_count_at->toDateTimeString());

        $biere = $count->lines()->where('product_id', $this->biere->id)->sole();
        $this->assertSame([13, 10, -3, 24, 30, 1, 'vol', 20], [
            $biere->expected, $biere->counted, $biere->gap, $biere->delivered, $biere->sold, $biere->lost, $biere->reason, $biere->startQuantity(),
        ]);
        $this->assertSame(['drink', 600, 1000], [$biere->kind, $biere->unit_cost, $biere->unit_price]);

        // Raison inconnue ignorée ; pas de raison quand c'est juste
        $this->assertNull($count->lines()->where('product_id', $this->coca->id)->sole()->reason);
        $this->assertNull($count->lines()->where('product_id', $this->poulet->id)->sole()->reason);

        // −3 × 1 000 + 1 × 500 au prix de vente ; −3 × 600 + 1 × 300 au prix d'achat
        $this->assertSame([-2500, -1500], [$count->gap_sale_value, $count->value_gap]);
        $this->assertSame([10 * 600 + 11 * 300 + 5 * 2000, 10 * 1000 + 11 * 500 + 5 * 3500], [$count->cost_value, $count->sale_value]);

        $this->assertSame('Disparu', StockMovement::where('type', 'inventory')->where('product_id', $this->biere->id)->sole()->reason);
        $this->assertSame(10, $this->biere->fresh()->stock_quantity);
    }

    public function test_le_resume_separe_boissons_et_nourriture(): void
    {
        $summary = (new StockCountReport($this->simpleCount()))->summary();

        $this->assertSame(['drink', 'food', 'total'], array_keys($summary));

        // Boissons : 17 bières + 11 cocas
        $this->assertSame([2, 13500, 22500, 9000, -2500, -1500], [
            $summary['drink']['products'], $summary['drink']['cost'], $summary['drink']['sale'],
            $summary['drink']['margin'], $summary['drink']['gap_sale'], $summary['drink']['gap_cost'],
        ]);
        // Nourriture : 5 poulets, juste
        $this->assertSame([1, 10000, 17500, 7500, 0], [
            $summary['food']['products'], $summary['food']['cost'], $summary['food']['sale'],
            $summary['food']['margin'], $summary['food']['gap_sale'],
        ]);
        $this->assertSame([3, 23500, 40000, 16500, -2500], [
            $summary['total']['products'], $summary['total']['cost'], $summary['total']['sale'],
            $summary['total']['margin'], $summary['total']['gap_sale'],
        ]);
    }

    public function test_le_message_whatsapp_du_comptage(): void
    {
        $report = new StockCountReport($this->simpleCount());
        $text = $report->toWhatsappText();

        $this->assertStringContainsString('*Comptage du stock · ' . $this->maquis->name . '*', $text);
        $this->assertStringContainsString('par Koffi', $text);
        $this->assertStringContainsString('Premier comptage', $text);
        $this->assertStringContainsString('*Boissons* · 2 produits comptés', $text);
        $this->assertStringContainsString('• Bière : -3 (-3 000 F) · disparu', $text);
        $this->assertStringContainsString('• Coca : +1 (+500 F)', $text);
        $this->assertStringContainsString("*Nourriture* · 1 produit compté\n• Tout est juste ✅", $text);
        $this->assertStringContainsString('• Bénéfice possible : 16 500 F', $text);
        $this->assertStringContainsString('• Écart : -2 500 F ⚠️', $text);
        $this->assertStringStartsWith('https://wa.me/?text=', $report->whatsappShareUrl());
    }

    // ---------- Écrans ----------

    public function test_ecran_du_premier_comptage(): void
    {
        $this->get(route('stock.count'))
            ->assertOk()
            ->assertSee('Comptage du stock')
            ->assertSee('Aucun comptage')
            ->assertSee('Jamais compté')
            ->assertSee('Doit rester')
            ->assertSee('Boissons')
            ->assertSee('Nourriture')
            ->assertSee('Bière');
    }

    public function test_ecran_apres_un_comptage_montre_la_periode_et_qui_tenait_la_caisse(): void
    {
        $this->stock()->recordInventory($this->patron, [$this->biere->id => '20']);
        $this->travel(3)->days();

        CashRegister::factory()->create(['restaurant_id' => $this->maquis->id, 'opened_by' => $this->awa->id, 'opened_at' => now()->subHour()]);
        $this->stock()->recordDelivery($this->mariam, [$this->biere->id => ['quantity' => 24, 'unit_cost' => 600]]);

        $this->get(route('stock.count'))
            ->assertOk()
            ->assertSee('Il y a 3 jours')
            ->assertSee('par Koffi')
            ->assertSee('Depuis le')
            ->assertSee('articles livrés')
            ->assertSee('+24 livrés')
            ->assertSee('Qui tenait la caisse')
            ->assertSee('Awa')
            ->assertSee('Comptages précédents');
    }

    public function test_valider_depuis_l_ecran_ouvre_le_resultat(): void
    {
        $component = Livewire::test(StockCountPage::class)
            ->call('save', [(string) $this->biere->id => '17'], [(string) $this->biere->id => 'casse'], 'Fin de semaine');

        $count = StockCount::sole();
        $component->assertRedirect(route('stock.count.show', $count));

        $this->assertSame('Fin de semaine', $count->note);
        $this->assertSame('casse', $count->lines()->sole()->reason);
        $this->assertSame(17, $this->biere->fresh()->stock_quantity);
    }

    public function test_valider_sans_chiffre_ou_avec_un_nombre_negatif_est_refuse(): void
    {
        Livewire::test(StockCountPage::class)
            ->call('save', [], [], '')
            ->assertReturned("Saisissez la quantité comptée d'au moins un produit.")
            ->call('save', [(string) $this->biere->id => '-4'], [], '')
            ->assertReturned('Les quantités comptées doivent être des nombres entiers positifs.');

        $this->assertSame(0, StockCount::count());
        $this->assertSame(20, $this->biere->fresh()->stock_quantity);
    }

    public function test_ecran_du_resultat(): void
    {
        $count = $this->simpleCount();

        $this->get(route('stock.count.show', $count))
            ->assertOk()
            ->assertSee('Les écarts')
            ->assertSee('Bière')
            ->assertSee('Disparu')
            ->assertSee('−3 000 F')
            ->assertSee('1 produit juste')
            ->assertSee('Bénéfice possible')
            ->assertSee('16 500 F')
            ->assertSee('Partager à quelqu');
    }

    public function test_la_caissiere_ne_compte_pas_le_stock(): void
    {
        $this->actingAs($this->awa)->get(route('stock.count'))->assertRedirect(route('tables.index'));

        $count = $this->simpleCount();
        $this->actingAs($this->awa)->get(route('stock.count.show', $count))->assertRedirect(route('tables.index'));
    }

    public function test_le_comptage_d_un_autre_maquis_est_introuvable(): void
    {
        $autre = Restaurant::factory()->create();
        $autrePatron = User::factory()->owner()->create(['restaurant_id' => $autre->id]);
        $autreProduit = Product::factory()->create(['restaurant_id' => $autre->id, 'stock_quantity' => 4]);

        $this->actingAs($autrePatron);
        $count = $this->stock()->recordInventory($autrePatron, [$autreProduit->id => '3']);

        $this->actingAs($this->patron)->get(route('stock.count.show', $count))->assertNotFound();
    }

    public function test_le_tableau_de_bord_rappelle_de_compter(): void
    {
        Livewire::test(Dashboard::class)
            ->assertSee('Stock compté')
            ->assertSee('Jamais');

        $this->simpleCount();

        Livewire::test(Dashboard::class)->assertSee("Aujourd'hui");
    }
}
