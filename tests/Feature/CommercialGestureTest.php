<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\Counter;
use App\Livewire\TakePayment;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Support\RegisterReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gestes commerciaux : article offert et remise, réservés au patron et à la
 * gérante, toujours avec un motif, et visibles dans le rapport du soir.
 */
class CommercialGestureTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $mariam;
    private User $jean;
    private User $aya;
    private Product $biere;
    private RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();

        $this->openRegister($this->maquis);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Koffi']);
        $this->mariam = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Mariam']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya']);
        $this->biere = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'name' => 'Bière', 'selling_price' => 1000, 'stock_quantity' => 15,
        ]);
        $this->table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'label' => 'Table 3']);
    }

    /** Bon de $qty bières (stock déjà sorti, comme à la prise de commande). */
    private function bon(int $qty = 5): Order
    {
        $order = Order::factory()->awaitingPayment($qty * 1000)->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $this->aya->id,
            'table_id' => $this->table->id,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id, 'product_id' => $this->biere->id,
            'quantity' => $qty, 'unit_price' => 1000, 'total' => $qty * 1000,
        ]);
        $this->biere->decrement('stock_quantity', $qty);

        return $order;
    }

    public function test_offrir_une_biere_sur_cinq_coupe_la_ligne_et_baisse_le_total(): void
    {
        $order = $this->bon(5);
        $item = $order->items()->sole();

        $offered = $order->offerItem($item->id, 1, $this->mariam, 'Bon client');

        $this->assertSame(4000, $order->fresh()->total);
        $this->assertSame(4, $item->fresh()->quantity);
        $this->assertTrue($offered->is_offered);
        $this->assertSame(1, $offered->quantity);
        $this->assertSame(0, $offered->total);
        $this->assertSame(1000, $offered->fullValue());
        $this->assertSame('Bon client', $offered->offered_reason);
        $this->assertSame($this->mariam->id, $offered->offered_by);
        // Le stock ne bouge pas : la bière a bien été bue
        $this->assertSame(10, $this->biere->fresh()->stock_quantity);
    }

    public function test_offrir_toute_une_ligne(): void
    {
        $order = $this->bon(2);

        $order->offerItem($order->items()->sole()->id, 2, $this->patron, 'Tournée du patron');

        $this->assertSame(0, $order->fresh()->total);
        $this->assertSame(1, $order->items()->count());
    }

    public function test_un_bon_entierement_offert_se_valide_a_zero(): void
    {
        $order = $this->bon(2);
        $order->offerItem($order->items()->sole()->id, 2, $this->patron, 'Tournée du patron');

        $payment = Payment::recordCashPayment($order->fresh(), $this->jean, 0);

        $this->assertSame(0, $payment->amount_due);
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_un_bon_vide_reste_refuse(): void
    {
        $order = Order::factory()->awaitingPayment(0)->create(['restaurant_id' => $this->maquis->id]);

        $this->expectException(BusinessRuleException::class);
        Payment::recordCashPayment($order, $this->jean, 0);
    }

    public function test_annuler_un_offert_rend_la_ligne_payante(): void
    {
        $order = $this->bon(3);
        $offered = $order->offerItem($order->items()->sole()->id, 1, $this->mariam, 'Erreur de service');

        $order->cancelOffer($offered->id, $this->mariam);

        $this->assertSame(3000, $order->fresh()->total);
        $this->assertFalse($offered->fresh()->is_offered);
    }

    public function test_remise_sur_le_bon(): void
    {
        $order = $this->bon(5);

        $order->applyDiscount(1500, $this->patron, 'Groupe de 10');

        $fresh = $order->fresh();
        $this->assertSame(5000, $fresh->subtotal);
        $this->assertSame(1500, $fresh->discount_amount);
        $this->assertSame(3500, $fresh->total);
        $this->assertSame($this->patron->id, $fresh->discount_by);

        // Retirer la remise
        $order->applyDiscount(0, $this->patron, '');
        $this->assertSame(5000, $order->fresh()->total);
    }

    public function test_la_remise_ne_peut_pas_depasser_le_bon(): void
    {
        $order = $this->bon(2);

        $this->expectException(BusinessRuleException::class);
        $order->applyDiscount(5000, $this->patron, 'Trop généreux');
    }

    public function test_le_motif_est_obligatoire(): void
    {
        $order = $this->bon(2);

        $this->expectException(BusinessRuleException::class);
        $order->offerItem($order->items()->sole()->id, 1, $this->patron, '  ');
    }

    public function test_la_caissiere_et_la_serveuse_ne_peuvent_pas_offrir(): void
    {
        $order = $this->bon(2);
        $itemId = $order->items()->sole()->id;

        foreach ([$this->jean, $this->aya] as $user) {
            try {
                $order->offerItem($itemId, 1, $user, 'Copine');
                $this->fail("{$user->role} a pu offrir.");
            } catch (BusinessRuleException) {
            }

            try {
                $order->applyDiscount(500, $user, 'Copine');
                $this->fail("{$user->role} a pu faire une remise.");
            } catch (BusinessRuleException) {
            }
        }

        $this->assertSame(2000, $order->fresh()->total);
    }

    public function test_un_bon_paye_ne_peut_plus_etre_modifie(): void
    {
        $order = $this->bon(2);
        Payment::recordCashPayment($order, $this->jean, 2000);

        $this->expectException(BusinessRuleException::class);
        $order->fresh()->offerItem($order->items()->sole()->id, 1, $this->patron, 'Après coup');
    }

    public function test_au_comptoir_la_gerante_offre_puis_encaisse(): void
    {
        $order = $this->bon(5);
        $item = $order->items()->sole();
        $this->actingAs($this->mariam);

        Livewire::test(Counter::class)
            ->call('startGestures', $order->id)
            ->call('selectOfferItem', $item->id)
            ->set('offerQuantity', 1)
            ->set('offerReason', 'Bon client')
            ->call('confirmOffer')
            ->assertHasNoErrors()
            ->assertSee('OFFERT')
            ->set('discountInput', 500)
            ->set('discountReason', 'Fidélité')
            ->call('applyDiscount')
            ->assertHasNoErrors()
            ->assertSee('Remise −500')
            ->call('closeGestures')
            ->call('startPayment', $order->id)
            ->assertSet('amountReceived', 3500)
            ->call('confirmPayment')
            ->assertHasNoErrors();

        $this->assertSame(3500, Payment::sole()->amount_due);
    }

    public function test_le_comptoir_cache_le_bouton_a_la_caissiere(): void
    {
        $order = $this->bon(2);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)
            ->assertDontSee('Offrir / remise')
            ->call('startGestures', $order->id)
            ->assertForbidden();
    }

    public function test_paiement_a_la_fin_le_patron_offre_depuis_l_encaissement(): void
    {
        $this->maquis->update(['payment_timing' => 'after']);
        $order = $this->bon(3);
        $this->actingAs($this->patron);

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('showGestures', true)
            ->assertSee('Offrir un article')
            ->call('selectOfferItem', $order->items()->sole()->id)
            ->set('offerReason', 'Bon client')
            ->call('confirmOffer')
            ->assertHasNoErrors()
            ->assertSet('amountReceived', 2000);
    }

    public function test_le_rapport_du_soir_liste_les_offerts_et_les_remises(): void
    {
        $caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->jean->id,
            'opening_amount' => 0,
            'opened_at' => now()->subHour(),
        ]);

        $a = $this->bon(5);
        $a->offerItem($a->items()->sole()->id, 2, $this->mariam, 'Bon client');
        Payment::recordCashPayment($a->fresh(), $this->jean, 3000);

        $b = $this->bon(4);
        $b->applyDiscount(1000, $this->patron, 'Groupe de 10');
        Payment::recordCashPayment($b->fresh(), $this->jean, 3000);

        $caisse->close(6000, $this->jean->id);
        $report = new RegisterReport($caisse->fresh());
        $text = $report->toWhatsappText();

        $this->assertSame(3000, $report->gesturesTotal);
        $this->assertStringContainsString('*Offerts et remises : 3 000 FCFA*', $text);
        $this->assertStringContainsString('• 2 × Bière offerts par Mariam (Bon client, Table 3)', $text);
        $this->assertStringContainsString('• Remise de 1 000 par Koffi (Groupe de 10, Table 3)', $text);
        $this->assertStringContainsString('*Ventes : 6 000 FCFA*', $text);
    }
}
