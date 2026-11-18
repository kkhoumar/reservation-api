<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\Counter;
use App\Livewire\Dashboard;
use App\Livewire\SalesToday;
use App\Livewire\TakePayment;
use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\RegisterReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Mobile Money (Wave, Orange, MTN, Moov), seul ou avec des espèces pour le reste.
 * Seule la part espèces entre dans la caisse ; tout apparaît dans les ventes.
 */
class MobileMoneyTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $jean;
    private User $patron;
    private User $aya;
    private CashRegister $caisse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create(['credit_enabled' => true]);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya']);
        $this->caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->jean->id,
            'opening_amount' => 10000,
            'opened_at' => now()->subHour(),
        ]);
    }

    private function bon(int $total): Order
    {
        return Order::factory()->awaitingPayment($total)->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $this->aya->id,
        ]);
    }

    public function test_paiement_100_pourcent_wave_au_comptoir(): void
    {
        $order = $this->bon(6000);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)
            ->call('startPayment', $order->id)
            ->set('paymentMethod', 'wave')
            ->assertSet('mobileAmount', 6000)
            ->assertSet('amountReceived', 0)
            ->set('reference', 'TX-123')
            ->call('confirmPayment')
            ->assertHasNoErrors()
            ->assertSee('encaissé (Wave)');

        $payment = Payment::sole();
        $this->assertSame('wave', $payment->payment_method);
        $this->assertSame(6000, $payment->mobile_amount);
        $this->assertSame(0, $payment->cashPart());
        $this->assertSame('TX-123', $payment->reference);
        $this->assertTrue($order->fresh()->isPaid());

        // Rien de plus dans le tiroir ; le Wave est affiché à part
        $breakdown = $this->caisse->breakdown();
        $this->assertSame(10000, $breakdown['theoretical']);
        $this->assertSame(['wave' => 6000], $breakdown['mobile_money']);
    }

    public function test_paiement_mixte_wave_et_especes_avec_monnaie(): void
    {
        $order = $this->bon(10000);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)
            ->call('startPayment', $order->id)
            ->set('paymentMethod', 'orange_money')
            ->set('mobileAmount', 6000)
            ->set('amountReceived', 5000)
            ->call('confirmPayment')
            ->assertHasNoErrors()
            ->assertSee('Orange Money + espèces')
            ->assertSee('Monnaie à rendre : 1 000 FCFA');

        $payment = Payment::sole();
        $this->assertSame(1000, $payment->change_given);
        $this->assertSame(4000, $payment->cashPart());
        $this->assertSame(14000, $this->caisse->calculateTheoreticalAmount());
    }

    public function test_il_manque_des_especes_pour_completer(): void
    {
        $order = $this->bon(10000);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)
            ->call('startPayment', $order->id)
            ->set('paymentMethod', 'wave')
            ->set('mobileAmount', 6000)
            ->set('amountReceived', 3000)
            ->call('confirmPayment')
            ->assertHasErrors('amountReceived')
            ->assertSee('Il manque 1 000 FCFA');

        $this->assertSame(0, Payment::count());
    }

    public function test_le_mobile_money_ne_peut_pas_depasser_le_total(): void
    {
        $this->expectException(BusinessRuleException::class);
        Payment::recordPayment($this->bon(5000), $this->jean, 0, 'wave', 8000);
    }

    public function test_l_operateur_est_obligatoire(): void
    {
        $this->expectException(BusinessRuleException::class);
        Payment::recordPayment($this->bon(5000), $this->jean, 0, 'carte_cadeau', 5000);
    }

    public function test_le_mobile_money_n_entre_pas_dans_la_sacoche(): void
    {
        $this->maquis->update(['payment_timing' => 'after']);

        Payment::recordPayment($this->bon(10000), $this->aya, 4000, 'wave', 6000);

        // Aya n'a sur elle que les 4 000 en espèces
        $this->assertSame(4000, $this->aya->sacocheBalance());
    }

    public function test_l_ecran_d_encaissement_accepte_le_mobile_money(): void
    {
        $order = $this->bon(3000);
        $this->actingAs($this->jean);

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->assertSet('amountReceived', 3000)
            ->set('paymentMethod', 'mtn_momo')
            ->call('confirmPayment')
            ->assertRedirect(route('payment.success', $order->id));

        $this->assertSame('mtn_momo', Payment::sole()->payment_method);

        $this->get(route('payment.success', $order->id))
            ->assertOk()
            ->assertSee('MTN MoMo');
    }

    public function test_un_paiement_en_especes_reste_comme_avant(): void
    {
        $payment = Payment::recordCashPayment($this->bon(3000), $this->jean, 5000);

        $this->assertSame('cash', $payment->payment_method);
        $this->assertSame(0, $payment->mobile_amount);
        $this->assertSame(2000, $payment->change_given);
        $this->assertSame('Espèces', $payment->methodLabel());
    }

    /** Une journée : 3 000 espèces, 6 000 Wave, 4 000 sur l'ardoise. */
    private function journee(): void
    {
        Payment::recordCashPayment($this->bon(3000), $this->jean, 3000);
        Payment::recordPayment($this->bon(6000), $this->jean, 0, 'wave', 6000, 'TX-9');
        $konan = Customer::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'M. Konan']);
        CreditTransaction::chargeOrder($this->bon(4000), $konan, $this->patron);
    }

    public function test_les_ventes_du_jour_montrent_tous_les_moyens_de_paiement(): void
    {
        $this->journee();
        $this->actingAs($this->jean);

        Livewire::test(SalesToday::class)
            ->assertSee('13 000 FCFA')
            ->assertSee('Espèces')
            ->assertSee('Wave')
            ->assertSee('TX-9')
            ->assertSee('Ardoise')
            ->assertSee('M. Konan');
    }

    public function test_le_tableau_de_bord_compte_l_ardoise_et_le_mobile_money(): void
    {
        $this->journee();
        $this->actingAs($this->patron);

        Livewire::test(Dashboard::class)
            ->assertViewHas('revenue', 13000)
            ->assertViewHas('orderCount', 3)
            ->assertSee('Mobile Money 6 000')
            ->assertSee('Ardoise 4 000');
    }

    public function test_le_rapport_du_soir_detaille_le_mobile_money(): void
    {
        $this->journee();
        $this->caisse->close(13000, $this->jean->id);

        $text = (new RegisterReport($this->caisse->fresh()))->toWhatsappText();

        $this->assertStringContainsString('*Ventes : 13 000 FCFA* (3 bons)', $text);
        $this->assertStringContainsString('• Espèces : 3 000', $text);
        $this->assertStringContainsString('• Wave : 6 000', $text);
        $this->assertStringContainsString("• Sur l'ardoise : 4 000", $text);
    }
}
