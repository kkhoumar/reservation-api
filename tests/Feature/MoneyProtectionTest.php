<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\CashRegisterManage;
use App\Livewire\NewOrder;
use App\Livewire\TakePayment;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Protection de l'argent et du stock : validation, doubles opérations,
 * commandes figées une fois à l'encaissement, stock toujours juste.
 */
class MoneyProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $caissier;
    private Product $flag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->caissier = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->flag = Product::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'name' => 'Flag',
            'selling_price' => 1000,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($this->caissier);
    }

    // ---------------------------------------------------------------
    // Prise de commande et stock
    // ---------------------------------------------------------------

    public function test_ajouter_un_produit_cree_la_commande_et_decremente_le_stock(): void
    {
        Livewire::test(NewOrder::class)
            ->set('quantity', 3)
            ->call('addProduct', $this->flag->id)
            ->assertHasNoErrors();

        $order = Order::sole();
        $this->assertSame(3000, $order->total);
        $this->assertStringStartsWith('CMD-', $order->order_number);
        $this->assertSame(7, $this->flag->fresh()->stock_quantity);
    }

    public function test_une_quantite_negative_ou_nulle_est_refusee(): void
    {
        foreach ([-5, 0] as $quantite) {
            Livewire::test(NewOrder::class)
                ->set('quantity', $quantite)
                ->call('addProduct', $this->flag->id)
                ->assertHasErrors('quantity');
        }

        $this->assertSame(0, OrderItem::count());
        $this->assertSame(10, $this->flag->fresh()->stock_quantity);
    }

    public function test_une_quantite_superieure_au_stock_est_refusee(): void
    {
        Livewire::test(NewOrder::class)
            ->set('quantity', 11)
            ->call('addProduct', $this->flag->id);

        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Order::count(), 'Aucune commande vide ne doit rester en base');
        $this->assertSame(10, $this->flag->fresh()->stock_quantity);
    }

    public function test_un_produit_indisponible_est_refuse(): void
    {
        $this->flag->update(['is_available' => false]);

        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id);

        $this->assertSame(0, OrderItem::count());
    }

    public function test_retirer_un_article_remet_le_stock(): void
    {
        $component = Livewire::test(NewOrder::class)
            ->set('quantity', 4)
            ->call('addProduct', $this->flag->id);

        $item = OrderItem::sole();

        $component->call('removeItem', $item->id);

        $this->assertSame(0, OrderItem::count());
        $this->assertSame(10, $this->flag->fresh()->stock_quantity);
        $this->assertSame(0, Order::sole()->total);
    }

    public function test_une_commande_a_l_encaissement_ne_peut_plus_etre_modifiee(): void
    {
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);

        $component = Livewire::test(NewOrder::class, ['table' => $table->id])
            ->call('addProduct', $this->flag->id)
            ->call('goToPayment');

        $item = OrderItem::sole();

        // Ajout et retrait refusés : le total et le stock ne bougent plus
        $component->call('addProduct', $this->flag->id)
            ->call('removeItem', $item->id);

        $this->assertSame(1, OrderItem::count());
        $this->assertSame(1000, Order::sole()->total);
        $this->assertSame(9, $this->flag->fresh()->stock_quantity);
    }

    public function test_impossible_d_envoyer_au_paiement_une_commande_vide(): void
    {
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);

        Livewire::test(NewOrder::class, ['table' => $table->id])
            ->call('goToPayment')
            ->assertNoRedirect();

        $this->assertSame(0, Order::count());
    }

    public function test_les_numeros_de_commande_se_suivent_sans_doublon(): void
    {
        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id);
        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id);

        $numeros = Order::orderBy('id')->pluck('order_number')->all();

        $this->assertCount(2, array_unique($numeros));
    }

    // ---------------------------------------------------------------
    // Encaissement
    // ---------------------------------------------------------------

    private function commandeAPayer(int $total = 5000): Order
    {
        return Order::factory()->awaitingPayment($total)->create(['restaurant_id' => $this->maquis->id]);
    }

    public function test_un_encaissement_correct_enregistre_le_paiement_et_la_monnaie(): void
    {
        $order = $this->commandeAPayer(5000);

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('amountReceived', 10000)
            ->call('confirmPayment')
            ->assertRedirect(route('payment.success', $order->id));

        $payment = Payment::sole();
        $this->assertSame(5000, $payment->change_given);
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_une_commande_ne_peut_pas_etre_payee_deux_fois(): void
    {
        $order = $this->commandeAPayer(5000);

        $component = Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('amountReceived', 5000)
            ->call('confirmPayment');

        // Deuxième clic (ou deuxième caissier)
        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('amountReceived', 5000)
            ->call('confirmPayment')
            ->assertNoRedirect();

        $this->assertSame(1, Payment::count());
    }

    public function test_le_modele_refuse_aussi_le_double_paiement(): void
    {
        $order = $this->commandeAPayer(5000);
        Payment::recordCashPayment($order, $this->caissier, 5000);

        $this->expectException(BusinessRuleException::class);

        Payment::recordCashPayment($order, $this->caissier, 5000);
    }

    public function test_un_montant_insuffisant_est_refuse(): void
    {
        $order = $this->commandeAPayer(5000);

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('amountReceived', 4000)
            ->call('confirmPayment')
            ->assertNoRedirect();

        $this->assertSame(0, Payment::count());
        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_un_montant_negatif_est_refuse(): void
    {
        $order = $this->commandeAPayer(5000);

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('amountReceived', -100)
            ->call('confirmPayment')
            ->assertHasErrors('amountReceived');

        $this->assertSame(0, Payment::count());
    }

    public function test_une_commande_vide_ne_peut_pas_etre_encaissee(): void
    {
        $order = $this->commandeAPayer(0);

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('amountReceived', 0)
            ->call('confirmPayment')
            ->assertNoRedirect();

        $this->assertSame(0, Payment::count());
    }

    // ---------------------------------------------------------------
    // Caisse
    // ---------------------------------------------------------------

    public function test_impossible_d_ouvrir_deux_caisses_en_meme_temps(): void
    {
        $component = Livewire::test(CashRegisterManage::class)
            ->set('openingAmount', 20000)
            ->call('openRegister');

        $component->set('openingAmount', 30000)->call('openRegister');

        $this->assertSame(1, CashRegister::count());
        $this->assertSame(20000, CashRegister::sole()->opening_amount);
    }

    public function test_un_fonds_de_caisse_negatif_est_refuse(): void
    {
        Livewire::test(CashRegisterManage::class)
            ->set('openingAmount', -500)
            ->call('openRegister')
            ->assertHasErrors('openingAmount');

        $this->assertSame(0, CashRegister::count());
    }

    public function test_la_cloture_calcule_l_ecart_et_ne_se_fait_qu_une_fois(): void
    {
        $register = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->caissier->id,
            'opening_amount' => 20000,
        ]);

        // Une vente de 5 000 F encaissée en espèces pendant la session
        Payment::recordCashPayment($this->commandeAPayer(5000), $this->caissier, 5000);

        Livewire::test(CashRegisterManage::class)
            ->set('countedAmount', 25000)
            ->call('confirmClose')
            ->assertRedirect(route('cash-register.closed', $register->id));

        $register->refresh();
        $this->assertSame(25000, $register->theoretical_amount);
        $this->assertSame(0, $register->discrepancy);
        $closedAt = $register->closed_at;

        // Nouvelle tentative : il n'y a plus de caisse ouverte, rien ne change
        Livewire::test(CashRegisterManage::class)
            ->set('countedAmount', 1)
            ->call('confirmClose');

        $this->assertEquals($closedAt, $register->fresh()->closed_at);
        $this->assertSame(25000, $register->fresh()->counted_amount);
    }

    public function test_un_ecart_important_exige_une_justification(): void
    {
        CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->caissier->id,
            'opening_amount' => 20000,
        ]);

        Livewire::test(CashRegisterManage::class)
            ->set('countedAmount', 15000)
            ->call('confirmClose')
            ->assertHasErrors('closingNote');

        $this->assertTrue(CashRegister::sole()->isOpen());
    }
}
