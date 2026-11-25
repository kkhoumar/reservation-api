<?php

namespace Tests\Feature;

use App\Actions\StockService;
use App\Livewire\ProductsManage;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\StockCountReport;
use App\Support\StockCountSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * « Vous rendez les bouteilles vides au dépôt ? » : Oui / Non, casier de 12,
 * 24 ou « Autre », et les vides cassés qui ne passent pas pour des bières disparues.
 */
class EmptyBottlesTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private Category $boissons;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons', 'kind' => 'drink']);
        $this->actingAs($this->patron);
    }

    private function nouvelleBoisson(string $name)
    {
        return Livewire::test(ProductsManage::class)
            ->call('openCreateForm')
            ->set('name', $name)
            ->set('categoryId', $this->boissons->id)
            ->set('sellingPrice', '700')
            ->set('costPrice', '450')
            ->set('stockQuantity', '24');
    }

    public function test_repondre_oui_propose_un_casier_de_12(): void
    {
        $this->nouvelleBoisson('Flag')
            ->assertSee('Vous rendez les bouteilles vides au dépôt', false)
            ->assertDontSee('Bouteille consignée')
            ->call('setReturnable', true)
            ->assertSet('crateSize', '12')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $flag = Product::where('name', 'Flag')->sole();
        $this->assertTrue($flag->is_returnable);
        $this->assertSame(12, $flag->crate_size);
    }

    public function test_autre_accepte_un_casier_de_30(): void
    {
        $this->nouvelleBoisson('Castel')
            ->call('setReturnable', true)
            ->set('crateSize', '30')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $this->assertSame(30, Product::where('name', 'Castel')->sole()->crate_size);
    }

    public function test_un_casier_de_300_est_refuse_avec_un_message_simple(): void
    {
        $this->nouvelleBoisson('Beaufort')
            ->call('setReturnable', true)
            ->set('crateSize', '300')
            ->call('saveProduct')
            ->assertHasErrors(['crateSize' => 'max'])
            ->assertSee('Un casier a entre 1 et 50 bouteilles. Vérifiez le nombre.');

        $this->nouvelleBoisson('Beaufort')
            ->call('setReturnable', true)
            ->set('crateSize', '')
            ->call('saveProduct')
            ->assertSee('Dites combien de bouteilles il y a dans un casier.');

        $this->assertSame(0, Product::where('name', 'Beaufort')->count());
    }

    public function test_par_defaut_la_reponse_est_non(): void
    {
        $this->nouvelleBoisson('Coca canette')->assertSet('isReturnable', false)->call('saveProduct')->assertHasNoErrors();

        $coca = Product::where('name', 'Coca canette')->sole();
        $this->assertFalse($coca->is_returnable);
        $this->assertNull($coca->crate_size);
    }

    public function test_le_comptage_connait_la_taille_du_casier(): void
    {
        Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id, 'name' => 'Flag', 'is_returnable' => true, 'crate_size' => 12, 'stock_quantity' => 30]);

        $row = (new StockCountSheet($this->maquis->id))->rows()->sole();
        $this->assertSame(12, $row['crate']);

        $this->get(route('stock.count'))->assertOk()
            ->assertSee('Ou en casiers de 12')
            ->assertSee('Bouteilles vides')
            ->assertSee('cassées');
    }

    public function test_un_vide_casse_n_est_pas_une_biere_disparue(): void
    {
        $flag = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id, 'name' => 'Flag',
            'selling_price' => 1000, 'cost_price' => 600, 'stock_quantity' => 30, 'is_returnable' => true, 'crate_size' => 12]);
        $stock = app(StockService::class);

        // 1er comptage : 30 pleines, 0 vide
        $stock->recordInventory($this->patron, [$flag->id => '30'], null, [], [$flag->id => '0']);
        $this->travel(1)->days();

        // 15 vendues avec bon → 15 pleines et 15 vides attendus
        $order = Order::factory()->awaitingPayment(15000)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->patron->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $flag->id, 'quantity' => 15, 'unit_price' => 1000, 'total' => 15000]);
        $flag->fresh()->decrementStock(15);

        // On trouve 10 pleines, 18 vides et 2 vides cassés : 5 manquent,
        // 3 + 2 = 5 ont été bues ici sans bon, aucune n'a disparu
        $count = $stock->recordInventory($this->patron, [$flag->id => '10'], null, [], [$flag->id => '18'], [$flag->id => '2']);

        $line = $count->lines()->sole();
        $this->assertSame([18, 2, 5, 0], [$line->empties_counted, $line->empties_broken, $line->unbilled, $line->taken_out]);
        // Les vides qui restent au maquis : ceux qu'on a trouvés (les cassés sont jetés)
        $this->assertSame(18, $flag->fresh()->empty_quantity);

        $text = (new StockCountReport($count))->toWhatsappText();
        $this->assertStringContainsString('5 peut-être vendues sans bon · 2 vides cassés', $text);
        $this->assertStringContainsString('Vérifiez avec votre équipe', $text);
    }
}
