<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\Ardoise;
use App\Livewire\Counter;
use App\Livewire\RestaurantSettings;
use App\Livewire\TakePayment;
use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * L'ardoise : le patron ou la gérante met un bon sur le compte d'un client,
 * le client rembourse plus tard en espèces (l'argent entre en caisse).
 */
class ArdoiseTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $gerante;
    private User $caissier;
    private User $aya;
    private Customer $konan;
    private CashRegister $caisse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create(['credit_enabled' => true]);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->gerante = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Mariam']);
        $this->caissier = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id]);
        $this->konan = Customer::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'name' => 'M. Konan',
            'phone' => '0707070707',
        ]);
        $this->caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->caissier->id,
            'opening_amount' => 10000,
            'opened_at' => now()->subHour(),
        ]);
    }

    private function bon(int $total = 3000, ?RestaurantTable $table = null): Order
    {
        return Order::factory()->awaitingPayment($total)->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $this->aya->id,
            'table_id' => $table?->id,
        ]);
    }

    public function test_la_gerante_met_un_bon_sur_l_ardoise_au_comptoir(): void
    {
        $order = $this->bon(3000);
        $this->actingAs($this->gerante);

        Livewire::test(Counter::class)
            ->call('startCredit', $order->id)
            ->assertSee('M. Konan')
            ->set('creditCustomerId', $this->konan->id)
            ->call('confirmCredit')
            ->assertHasNoErrors();

        $this->assertSame(3000, $this->konan->balance());
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame($this->konan->id, $order->fresh()->customer_id);
        $this->assertSame($this->gerante->id, CreditTransaction::sole()->user_id);
        // Aucun argent n'entre en caisse
        $this->assertSame(0, Payment::count());
        $this->assertSame(10000, $this->caisse->calculateTheoreticalAmount());
    }

    public function test_la_caissiere_ne_peut_pas_mettre_sur_l_ardoise(): void
    {
        $order = $this->bon();
        $this->actingAs($this->caissier);

        Livewire::test(Counter::class)
            ->assertDontSee("Sur l'ardoise")
            ->call('startCredit', $order->id)
            ->assertForbidden();

        $this->expectException(BusinessRuleException::class);
        CreditTransaction::chargeOrder($order, $this->konan, $this->caissier);
    }

    public function test_impossible_si_l_ardoise_n_est_pas_activee(): void
    {
        $this->maquis->update(['credit_enabled' => false]);
        $order = $this->bon();

        $this->expectException(BusinessRuleException::class);
        CreditTransaction::chargeOrder($order, $this->konan, $this->patron);
    }

    public function test_le_plafond_bloque_un_nouveau_credit(): void
    {
        $this->konan->update(['credit_limit' => 5000]);
        CreditTransaction::chargeOrder($this->bon(3000), $this->konan, $this->patron);

        try {
            CreditTransaction::chargeOrder($this->bon(3000), $this->konan, $this->patron);
            $this->fail('Plafond dépassé accepté.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('Plafond dépassé', $e->getMessage());
        }

        $this->assertSame(3000, $this->konan->balance());
    }

    public function test_un_bon_deja_regle_ne_va_pas_sur_l_ardoise(): void
    {
        $order = $this->bon(3000);
        Payment::recordCashPayment($order, $this->caissier, 3000);

        $this->expectException(BusinessRuleException::class);
        CreditTransaction::chargeOrder($order, $this->konan, $this->patron);
    }

    public function test_impossible_d_utiliser_le_client_d_un_autre_maquis(): void
    {
        $autre = Customer::factory()->create();

        $this->expectException(BusinessRuleException::class);
        CreditTransaction::chargeOrder($this->bon(), $autre, $this->patron);
    }

    public function test_le_remboursement_baisse_la_dette_et_entre_en_caisse(): void
    {
        CreditTransaction::chargeOrder($this->bon(5000), $this->konan, $this->patron);
        $this->actingAs($this->caissier);

        Livewire::test(Ardoise::class)
            ->call('select', $this->konan->id)
            ->set('repaymentAmount', 3000)
            ->call('recordRepayment')
            ->assertHasNoErrors();

        $this->assertSame(2000, $this->konan->balance());

        $breakdown = $this->caisse->breakdown();
        $this->assertSame(1, $breakdown['repayments_count']);
        $this->assertSame(3000, $breakdown['repayments_total']);
        $this->assertSame(13000, $breakdown['theoretical']);
    }

    public function test_impossible_de_rembourser_plus_que_la_dette(): void
    {
        CreditTransaction::chargeOrder($this->bon(2000), $this->konan, $this->patron);
        $this->actingAs($this->caissier);

        Livewire::test(Ardoise::class)
            ->call('select', $this->konan->id)
            ->set('repaymentAmount', 5000)
            ->call('recordRepayment')
            ->assertHasErrors('repaymentAmount');

        $this->assertSame(2000, $this->konan->balance());
    }

    public function test_pas_de_remboursement_sans_caisse_ouverte(): void
    {
        CreditTransaction::chargeOrder($this->bon(2000), $this->konan, $this->patron);
        $this->caisse->close(10000, $this->caissier->id);

        $this->expectException(BusinessRuleException::class);
        CreditTransaction::recordRepayment($this->konan, $this->caissier, 2000);
    }

    public function test_le_patron_ajoute_un_client_avec_plafond(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(Ardoise::class)
            ->set('name', 'Tantie Awa')
            ->set('phone', '+225 05 11 22 33 44')
            ->set('creditLimit', 20000)
            ->call('createCustomer')
            ->assertHasNoErrors();

        $awa = Customer::where('name', 'Tantie Awa')->sole();
        $this->assertSame('0511223344', $awa->phone);
        $this->assertSame(20000, $awa->credit_limit);
    }

    public function test_un_numero_deja_utilise_est_refuse(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(Ardoise::class)
            ->set('name', 'Doublon')
            ->set('phone', '07 07 07 07 07')
            ->call('createCustomer')
            ->assertHasErrors('phone');
    }

    public function test_la_caissiere_ne_cree_pas_de_client(): void
    {
        $this->actingAs($this->caissier);

        Livewire::test(Ardoise::class)
            ->set('name', 'Nouveau')
            ->call('createCustomer')
            ->assertForbidden();
    }

    public function test_l_ardoise_liste_les_dettes_et_propose_la_relance_whatsapp(): void
    {
        CreditTransaction::chargeOrder($this->bon(4500), $this->konan, $this->patron);
        $this->actingAs($this->gerante);

        Livewire::test(Ardoise::class)
            ->assertSee('M. Konan')
            ->assertSee('4 500 FCFA')
            ->call('select', $this->konan->id)
            ->assertSee('Relancer sur WhatsApp')
            ->assertSee('https://wa.me/2250707070707', false);
    }

    public function test_paiement_a_la_fin_le_patron_met_sur_l_ardoise_depuis_l_encaissement(): void
    {
        $this->maquis->update(['payment_timing' => 'after']);
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'status' => 'awaiting_payment']);
        $order = $this->bon(6000, $table);
        $this->actingAs($this->patron);

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->set('creditCustomerId', $this->konan->id)
            ->call('chargeToCustomer')
            ->assertRedirect(route('ardoise'));

        $this->assertSame(6000, $this->konan->balance());
        $this->assertSame('free', $table->fresh()->status);
    }

    public function test_impossible_de_desactiver_l_ardoise_avec_des_dettes(): void
    {
        CreditTransaction::chargeOrder($this->bon(2000), $this->konan, $this->patron);
        $this->actingAs($this->patron);

        Livewire::test(RestaurantSettings::class)
            ->set('creditEnabled', false)
            ->call('save')
            ->assertHasErrors('creditEnabled');

        $this->assertTrue($this->maquis->fresh()->creditEnabled());
    }

    public function test_l_ardoise_est_inaccessible_si_desactivee(): void
    {
        $this->maquis->update(['credit_enabled' => false]);

        $this->actingAs($this->patron)
            ->get(route('ardoise'))
            ->assertRedirect(route('tables.index'));
    }

    public function test_une_serveuse_n_a_pas_acces_a_l_ardoise(): void
    {
        $this->actingAs($this->aya)
            ->get(route('ardoise'))
            ->assertRedirect(route('tables.index'));
    }
}
