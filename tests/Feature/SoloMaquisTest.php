<?php

namespace Tests\Feature;

use App\Livewire\NewOrder;
use App\Livewire\TakePayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Petit maquis : une seule personne prend la commande, encaisse et sert.
 * L'application s'adapte au personnel créé : sans serveuse, pas de comptoir
 * ni de sacoche, et le gros bouton est « Encaisser ».
 */
class SoloMaquisTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private Product $biere;
    private RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();

        $this->openRegister($this->maquis);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->biere = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'name' => 'Bière', 'selling_price' => 1000, 'stock_quantity' => 20,
        ]);
        $this->table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'label' => 'Table 1']);
    }

    private function ajouterServeuse(string $status = 'active'): User
    {
        return User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'status' => $status]);
    }

    public function test_seul_le_patron_encaisse_directement(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->call('addProduct', $this->biere->id)
            ->assertSee('Encaisser')
            ->assertDontSee('Envoyer au comptoir')
            ->call('goToPayment')
            ->assertRedirect(route('payment.take', Order::sole()->id));

        $order = Order::sole();
        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->call('confirmPayment')
            ->assertRedirect(route('payment.success', $order->id));

        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_sans_serveuse_pas_de_comptoir_ni_de_sacoches_dans_le_menu(): void
    {
        $this->actingAs($this->patron)
            ->get(route('tables.index'))
            ->assertOk()
            ->assertDontSee(route('counter'), false)
            ->assertDontSee(route('sacoches'), false);
    }

    public function test_une_serveuse_desactivee_ne_compte_pas(): void
    {
        $this->ajouterServeuse('inactive');

        $this->assertFalse($this->maquis->hasActiveServers());
        $this->actingAs($this->patron)
            ->get(route('tables.index'))
            ->assertDontSee(route('counter'), false);
    }

    public function test_des_qu_une_serveuse_existe_le_comptoir_reapparait(): void
    {
        $this->ajouterServeuse();

        $this->actingAs($this->patron)
            ->get(route('tables.index'))
            ->assertSee(route('counter'), false);

        // Le patron garde « Encaisser » en gros et peut aussi envoyer au comptoir
        Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->call('addProduct', $this->biere->id)
            ->assertSee('Encaisser')
            ->assertSee('Envoyer au comptoir');
    }

    public function test_la_serveuse_ne_voit_que_envoyer_au_comptoir(): void
    {
        $aya = $this->ajouterServeuse();
        $this->actingAs($aya);

        Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->call('addProduct', $this->biere->id)
            ->assertSee('Envoyer au comptoir')
            ->assertDontSee('wire:click="goToPayment"', false);
    }

    public function test_un_bon_en_attente_garde_le_comptoir_visible(): void
    {
        Order::factory()->awaitingPayment(2000)->create(['restaurant_id' => $this->maquis->id]);

        $this->actingAs($this->patron)
            ->get(route('tables.index'))
            ->assertSee(route('counter'), false);
    }

    public function test_paiement_a_la_fin_les_sacoches_n_apparaissent_qu_avec_des_serveuses(): void
    {
        $this->maquis->update(['payment_timing' => 'after']);
        $this->actingAs($this->patron);

        $this->get(route('tables.index'))->assertDontSee(route('sacoches'), false);

        $this->ajouterServeuse();
        $this->get(route('tables.index'))->assertSee(route('sacoches'), false);
    }

    public function test_apres_paiement_on_enchaine_sur_la_vente_suivante(): void
    {
        $this->actingAs($this->patron);

        $surTable = Order::factory()->awaitingPayment(1000)->create([
            'restaurant_id' => $this->maquis->id, 'table_id' => $this->table->id,
        ]);
        Payment::recordCashPayment($surTable, $this->patron, 1000);

        $this->get(route('payment.success', $surTable->id))
            ->assertSee('Nouvelle tournée — Table 1')
            ->assertSee(route('orders.new', ['table' => $this->table->id]), false);

        $emporter = Order::factory()->awaitingPayment(1000)->create(['restaurant_id' => $this->maquis->id]);
        Payment::recordCashPayment($emporter, $this->patron, 1000);

        $this->get(route('payment.success', $emporter->id))
            ->assertSee('Nouvelle vente');
    }
}
