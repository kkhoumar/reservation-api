<?php

namespace Tests\Feature;

use App\Livewire\ActivityJournal;
use App\Livewire\Login;
use App\Livewire\ProductsManage;
use App\Livewire\UsersManage;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * Journal des actions : chaque action sensible garde le nom de la personne,
 * le patron et la gérante le consultent, personne ne peut le modifier.
 */
class ActivityJournalTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;
    private Product $biere;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Koffi', 'last_name' => 'Yao']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean', 'last_name' => 'Kouassi']);
        $boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons']);
        $this->biere = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'category_id' => $boissons->id,
            'name' => 'Bière', 'selling_price' => 1000, 'cost_price' => 600, 'stock_quantity' => 20,
        ]);
    }

    private function bon(int $quantity = 2): Order
    {
        $order = Order::factory()->awaitingPayment($quantity * 1000)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->patron->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $this->biere->id, 'quantity' => $quantity, 'unit_price' => 1000, 'total' => $quantity * 1000]);

        return $order;
    }

    public function test_une_connexion_est_notee(): void
    {
        Livewire::test(Login::class)
            ->set('phone', $this->jean->phone)
            ->set('password', 'password')
            ->call('authenticate');

        $log = ActivityLog::withoutGlobalScopes()->sole();
        $this->assertSame(['login', $this->jean->id, $this->maquis->id], [$log->action, $log->user_id, $log->restaurant_id]);
    }

    public function test_un_bon_annule_garde_qui_l_a_annule(): void
    {
        $order = $this->bon(3);
        $this->actingAs($this->jean);

        $order->cancel();

        $order->refresh();
        $this->assertSame($this->jean->id, $order->cancelled_by);
        $this->assertNotNull($order->cancelled_at);

        $log = ActivityLog::where('action', 'order_cancelled')->sole();
        $this->assertSame([$this->jean->id, -3000, 'danger'], [$log->user_id, $log->amount, $log->severity()]);
        $this->assertStringContainsString("a annulé le bon {$order->order_number} de 3 000 F (3 × Bière)", $log->description);
    }

    public function test_un_changement_de_prix_est_note_avec_l_ancien_et_le_nouveau(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(ProductsManage::class)
            ->call('editProduct', $this->biere->id)
            ->set('sellingPrice', '900')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $log = ActivityLog::where('action', 'price_changed')->sole();
        $this->assertSame('a changé le prix de Bière : prix de vente 1 000 F → 900 F.', $log->description);
        $this->assertSame('warning', $log->severity());
    }

    public function test_une_remise_et_un_offert_sont_notes(): void
    {
        $order = $this->bon(2);
        $this->actingAs($this->patron);

        $order->applyDiscount(500, $this->patron, 'Bon client');
        $order->offerItem($order->items()->first()->id, 1, $this->patron, 'Anniversaire');

        $this->assertSame(['discount', 'item_offered'], ActivityLog::orderBy('id')->pluck('action')->all());
        $this->assertStringContainsString('remise de 500 F', ActivityLog::where('action', 'discount')->value('description'));
    }

    public function test_desactiver_un_employe_est_note(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)->call('toggleStatus', $this->jean->id);

        $log = ActivityLog::where('action', 'user_deactivated')->sole();
        $this->assertSame('a désactivé le compte de Jean Kouassi.', $log->description);
        $this->assertSame($this->patron->id, $log->user_id);
    }

    public function test_le_journal_ne_se_modifie_ni_ne_s_efface(): void
    {
        $this->actingAs($this->patron);
        ActivityLog::record('login', "s'est connecté.");
        $log = ActivityLog::sole();

        try {
            $log->update(['description' => 'rien']);
            $this->fail('La modification aurait dû être refusée.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_ecran_du_journal_pour_le_patron(): void
    {
        $this->actingAs($this->jean);
        $this->bon(1)->cancel();

        $this->actingAs($this->patron)
            ->get(route('journal'))
            ->assertOk()
            ->assertSee('Journal des actions')
            ->assertSee('Jean Kouassi')
            ->assertSee('Bon annulé')
            ->assertSee('a annulé le bon');

        Livewire::test(ActivityJournal::class)
            ->assertViewHas('stats', fn ($s) => $s['cancelled'] === 1 && $s['cancelled_amount'] === 1000 && $s['sensitive'] === 1)
            ->call('setGroup', 'login')
            ->assertDontSee('a annulé le bon')
            ->call('setGroup', 'sensitive')
            ->assertSee('a annulé le bon');
    }

    public function test_la_caissiere_ne_voit_pas_le_journal(): void
    {
        $this->actingAs($this->jean)->get(route('journal'))->assertRedirect(route('tables.index'));
    }

    public function test_le_journal_d_un_autre_maquis_reste_invisible(): void
    {
        $autre = Restaurant::factory()->create();
        $autrePatron = User::factory()->owner()->create(['restaurant_id' => $autre->id, 'first_name' => 'Awa', 'last_name' => 'Traoré']);
        ActivityLog::record('login', "s'est connecté.", ['user' => $autrePatron]);

        $this->actingAs($this->patron)
            ->get(route('journal'))
            ->assertOk()
            ->assertDontSee('Awa Traoré');
    }
}
