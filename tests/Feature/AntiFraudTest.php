<?php

namespace Tests\Feature;

use App\Actions\StockService;
use App\Livewire\Dashboard;
use App\Livewire\StockManage;
use App\Livewire\UsersManage;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\StockCountReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Contre la triche : rôle Associé (consulte sans rien modifier), photo
 * obligatoire pour une perte, contrôle des bouteilles vides consignées.
 */
class AntiFraudTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $associe;
    private Product $flag;
    private Product $canette;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Koffi']);
        $this->associe = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'associate', 'first_name' => 'Diallo']);

        $boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons']);
        $this->flag = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $boissons->id, 'name' => 'Flag',
            'selling_price' => 700, 'cost_price' => 450, 'stock_quantity' => 24, 'is_returnable' => true, 'crate_size' => 24,
        ]);
        $this->canette = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $boissons->id, 'name' => 'Coca canette',
            'selling_price' => 600, 'cost_price' => 400, 'stock_quantity' => 12,
        ]);
    }

    private function stock(): StockService
    {
        return app(StockService::class);
    }

    private function jpeg(): string
    {
        $img = imagecreatetruecolor(200, 150);
        imagefill($img, 0, 0, imagecolorallocate($img, 120, 60, 20));
        ob_start();
        imagejpeg($img, null, 80);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    /** Vente enregistrée normalement (comme l'écran de commande). */
    private function vendre(Product $product, int $quantity): void
    {
        $order = Order::factory()->awaitingPayment($quantity * $product->selling_price)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->patron->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $product->selling_price, 'total' => $quantity * $product->selling_price]);
        $product->fresh()->decrementStock($quantity);
    }

    // ---------- Associé ----------

    public function test_l_associe_consulte_mais_ne_peut_rien_enregistrer(): void
    {
        $this->actingAs($this->associe);

        $this->get(route('dashboard.index'))->assertOk();
        $this->get(route('journal'))->assertOk();
        $this->get(route('stock.index'))->assertOk()->assertDontSee('Compter le stock');
        $this->get(route('stock.count'))->assertOk()->assertSee('Compte associé');

        // Écrans pour vendre ou gérer : retour au tableau de bord
        $this->get(route('cash-register.manage'))->assertRedirect(route('dashboard.index'));
        $this->get(route('tables.index'))->assertRedirect(route('dashboard.index'));
        $this->get(route('products.manage'))->assertRedirect(route('dashboard.index'));

        // Même en appelant directement une action, rien n'est enregistré
        Livewire::test(StockManage::class)
            ->set('lossProductId', $this->flag->id)
            ->set('lossQuantity', 2)
            ->set('lossReason', 'Casse')
            ->set('lossPhoto', $this->jpeg())
            ->call('saveLoss');

        $this->assertSame(24, $this->flag->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::where('type', 'loss')->count());
    }

    public function test_le_patron_cree_un_associe_mais_ne_peut_ensuite_ni_le_modifier_ni_le_desactiver(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)
            ->call('openCreateForm')
            ->set('firstName', 'Awa')->set('lastName', 'Traoré')->set('phone', '0700000001')
            ->set('password', 'provisoire-2026')->set('role', 'associate')
            ->call('save')
            ->assertHasNoErrors();

        $awa = User::where('phone', '0700000001')->sole();
        $this->assertTrue($awa->isAssociate());

        Livewire::test(UsersManage::class)->call('toggleStatus', $awa->id)->assertForbidden();
        $this->assertSame('active', $awa->fresh()->status);

        Livewire::test(UsersManage::class)->call('removeUser', $awa->id)->assertForbidden();
        $this->assertNull($awa->fresh()->left_at);
    }

    // ---------- Photo obligatoire ----------

    public function test_une_perte_demande_une_photo(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(StockManage::class)
            ->set('lossProductId', $this->flag->id)
            ->set('lossQuantity', 2)
            ->set('lossReason', 'Casse')
            ->call('saveLoss')
            ->assertHasErrors(['lossPhoto' => 'required'])
            ->set('lossPhoto', $this->jpeg())
            ->call('saveLoss')
            ->assertHasNoErrors();

        $loss = StockMovement::where('type', 'loss')->sole();
        $this->assertNotNull($loss->photo_path);
        Storage::disk('local')->assertExists($loss->photo_path);
        $this->assertStringContainsString('Photo jointe', ActivityLog::where('action', 'stock_loss')->value('description'));

        // La photo s'ouvre pour le patron et l'associé, pas pour un autre maquis
        $this->get(route('stock.photo', $loss))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($this->associe)->get(route('stock.photo', $loss))->assertOk();
        $autre = User::factory()->owner()->create(['restaurant_id' => Restaurant::factory()->create()->id]);
        $this->actingAs($autre)->get(route('stock.photo', $loss))->assertNotFound();
    }

    // ---------- Bouteilles vides ----------

    public function test_une_vente_de_bouteille_consignee_ajoute_un_vide_attendu_et_l_annulation_le_retire(): void
    {
        $this->actingAs($this->patron);
        $this->vendre($this->flag, 3);
        $this->vendre($this->canette, 2);

        $this->assertSame([21, 3], [$this->flag->fresh()->stock_quantity, $this->flag->fresh()->empty_quantity]);
        $this->assertSame(0, $this->canette->fresh()->empty_quantity); // canette jetée : pas de vide

        Product::restoreStock($this->flag->id, 1);
        $this->assertSame([22, 2], [$this->flag->fresh()->stock_quantity, $this->flag->fresh()->empty_quantity]);
    }

    public function test_le_comptage_des_vides_explique_les_bouteilles_qui_manquent(): void
    {
        $this->actingAs($this->patron);

        // 1er comptage : 24 pleines, 0 vide → les vides deviennent la référence
        $this->stock()->recordInventory($this->patron, [$this->flag->id => '24'], null, [], [$this->flag->id => '0']);
        $this->travel(2)->days();

        // 10 vendues avec bon → l'application attend 14 pleines et 10 vides
        $this->vendre($this->flag, 10);

        // On trouve 9 pleines et 13 vides : 5 manquent, 3 vides en trop
        $count = $this->stock()->recordInventory($this->patron, [$this->flag->id => '9'], null, [], [$this->flag->id => '13']);

        $line = $count->lines()->sole();
        $this->assertSame([14, 9, -5, 10, 13, 3, 2], [
            $line->expected, $line->counted, $line->gap, $line->empties_expected, $line->empties_counted, $line->unbilled, $line->taken_out,
        ]);
        $this->assertSame(3 * 700, $count->unbilled_value);
        $this->assertSame(13, $this->flag->fresh()->empty_quantity);

        $text = (new StockCountReport($count))->toWhatsappText();
        $this->assertStringContainsString('• Flag : -5 (-3 500 F) · 3 peut-être vendues sans bon · 2 disparues (ni bon ni vide)', $text);
        $this->assertStringContainsString('Peut-être vendu sans bon (vides en trop) : 2 100 F', $text);
        $this->assertStringContainsString('Dont 2 100 F de boissons peut-être vendues sans bon', ActivityLog::where('action', 'stock_count')->latest('id')->value('description'));
    }

    public function test_sans_comptage_des_vides_prealable_pas_de_conclusion(): void
    {
        $this->actingAs($this->patron);
        $count = $this->stock()->recordInventory($this->patron, [$this->flag->id => '20'], null, [], [$this->flag->id => '7']);

        $line = $count->lines()->sole();
        $this->assertSame([null, 7, 0, 0], [$line->empties_expected, $line->empties_counted, $line->unbilled, $line->taken_out]);
        $this->assertNotNull($this->flag->fresh()->empties_counted_at);
    }

    public function test_une_livraison_avec_trop_de_vides_rendus_donne_l_alerte(): void
    {
        $this->actingAs($this->patron);
        $this->stock()->recordInventory($this->patron, [$this->flag->id => '24'], null, [], [$this->flag->id => '0']);
        $this->travel(1)->days();
        $this->vendre($this->flag, 20); // 20 vides attendus

        // Le fournisseur reprend 24 vides contre 24 pleines : 4 vides de trop
        $result = $this->stock()->recordDelivery($this->patron, [$this->flag->id => ['quantity' => 24, 'unit_cost' => 450]]);

        $this->assertSame([['product' => 'Flag', 'surplus' => 4, 'value' => 2800]], $result['empties_alerts']);
        $this->assertSame(4, StockMovement::where('type', 'delivery')->sole()->empties_surplus);
        $this->assertSame(0, $this->flag->fresh()->empty_quantity);

        $alert = ActivityLog::where('action', 'empties_alert')->sole();
        $this->assertSame('danger', $alert->severity());
        $this->assertStringContainsString('4 vides de Flag rendus en plus', $alert->description);
    }

    public function test_une_livraison_normale_ne_donne_pas_d_alerte(): void
    {
        $this->actingAs($this->patron);
        $this->stock()->recordInventory($this->patron, [$this->flag->id => '24'], null, [], [$this->flag->id => '0']);
        $this->travel(1)->days();
        $this->vendre($this->flag, 24);

        $result = $this->stock()->recordDelivery($this->patron, [$this->flag->id => ['quantity' => 24, 'unit_cost' => 450]]);

        $this->assertSame([], $result['empties_alerts']);
        $this->assertSame(0, ActivityLog::where('action', 'empties_alert')->count());
    }
}
