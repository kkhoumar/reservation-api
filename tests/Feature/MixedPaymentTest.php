<?php

namespace Tests\Feature;

use App\Livewire\Counter;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bouton « Espèces + Mobile Money » : le client a 2 000 F en espèces et
 * 200 F sur Wave ou Orange ; la caissière tape les deux montants.
 * Boutons des billets : « Compte juste », 5 000, 10 000…
 */
class MixedPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $jean;
    private CashRegister $caisse;
    private Order $bon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->caisse = $this->openRegister($this->maquis, $this->jean, 10000);
        $aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id]);
        $this->bon = Order::factory()->awaitingPayment(2200)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $aya->id]);
        $this->actingAs($this->jean);
    }

    public function test_2000_en_especes_et_200_par_orange_money(): void
    {
        Livewire::test(Counter::class)
            ->call('startPayment', $this->bon->id)
            ->call('chooseMixed')
            ->assertSet('mixed', true)
            ->assertSet('mobileAmount', null)
            ->assertSet('amountReceived', null)
            ->assertSee('Espèces + Mobile Money')
            ->set('paymentMethod', 'orange_money')
            ->set('mobileAmount', 200)
            ->set('amountReceived', 2000)
            ->assertSet('mobileAmount', 200) // changer d'opérateur ne remet pas les montants à zéro
            ->assertSee('Payé')
            ->call('confirmPayment')
            ->assertHasNoErrors();

        $payment = Payment::sole();
        $this->assertSame(['orange_money', 200, 2000, 0], [$payment->payment_method, $payment->mobile_amount, $payment->amount_received, $payment->change_given]);
        $this->assertTrue($this->bon->fresh()->isPaid());

        // Le tiroir reçoit seulement les 2 000 F d'espèces
        $this->assertSame(12000, $this->caisse->calculateTheoreticalAmount());
        $this->assertSame(['orange_money' => 200], $this->caisse->breakdown()['mobile_money']);
    }

    public function test_il_manque_de_l_argent(): void
    {
        Livewire::test(Counter::class)
            ->call('startPayment', $this->bon->id)
            ->call('chooseMixed')
            ->set('mobileAmount', 200)
            ->set('amountReceived', 1500)
            ->assertSee('Il manque')
            ->assertSee('500 F')
            ->call('confirmPayment');

        $this->assertSame(0, Payment::count());
        $this->assertFalse($this->bon->fresh()->isPaid());
    }

    public function test_les_boutons_des_billets_calculent_la_monnaie(): void
    {
        Livewire::test(Counter::class)
            ->call('startPayment', $this->bon->id)
            ->call('chooseMixed')
            ->set('mobileAmount', 200)
            ->assertSee('Compte juste')
            ->assertSee('5 000')
            ->call('setCashReceived', 5000)
            ->assertSet('amountReceived', 5000)
            ->assertSee('3 000 F')
            ->call('confirmPayment')
            ->assertHasNoErrors();

        $this->assertSame(3000, Payment::sole()->change_given);
    }

    public function test_compte_juste_en_especes_seulement(): void
    {
        Livewire::test(Counter::class)
            ->call('startPayment', $this->bon->id)
            ->call('setCashReceived', 0)
            ->call('setCashReceived', 2200)
            ->assertSet('amountReceived', 2200)
            ->call('confirmPayment')
            ->assertHasNoErrors();

        $this->assertSame([2200, 0], [Payment::sole()->amount_received, Payment::sole()->change_given]);
    }

    public function test_revenir_aux_especes_quitte_le_paiement_mixte(): void
    {
        Livewire::test(Counter::class)
            ->call('startPayment', $this->bon->id)
            ->call('chooseMixed')
            ->call('choosePayment', 'cash')
            ->assertSet('mixed', false)
            ->assertSet('paymentMethod', 'cash')
            ->assertSet('amountReceived', 2200)
            ->call('choosePayment', 'wave')
            ->assertSet('mobileAmount', 2200)
            ->assertSet('amountReceived', 0);
    }
}
