<?php

namespace Tests\Feature;

use App\Livewire\Counter;
use App\Livewire\NewOrder;
use App\Livewire\RestaurantSettings;
use App\Livewire\TakePayment;
use App\Models\CashRegister;
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
 * Paiement avant service : la serveuse envoie le bon au comptoir,
 * le comptoir encaisse l'argent qu'elle apporte, puis on sert.
 */
class CounterTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $aya;
    private User $jean;
    private Product $biere;
    private RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->biere = Product::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'name' => 'Bière',
            'selling_price' => 1000,
            'stock_quantity' => 20,
        ]);
        $this->table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'label' => 'Table 4']);
    }

    /** Aya prend une tournée de $n bières sur la table et l'envoie au comptoir. */
    private function envoyerTournee(int $n = 3): Order
    {
        $this->actingAs($this->aya);

        Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->set('quantity', $n)
            ->call('addProduct', $this->biere->id)
            ->call('sendToCounter')
            ->assertRedirect(route('tables.index'));

        return Order::latest('id')->first();
    }

    public function test_un_nouveau_maquis_paie_avant_service_par_defaut(): void
    {
        $this->assertTrue(Restaurant::factory()->create()->paysBeforeService());
    }

    public function test_la_serveuse_envoie_le_bon_au_comptoir(): void
    {
        $order = $this->envoyerTournee(3);

        $this->assertSame('awaiting_payment', $order->status);
        $this->assertSame(3000, $order->total);
        $this->assertSame($this->aya->id, $order->user_id);
        $this->assertSame('occupied', $this->table->fresh()->status);
        $this->assertSame(0, Payment::count());
    }

    public function test_la_serveuse_ne_peut_pas_encaisser_elle_meme(): void
    {
        $order = $this->envoyerTournee();

        $this->actingAs($this->aya);

        Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->call('goToPayment')
            ->assertNoRedirect();

        Livewire::test(TakePayment::class, ['order' => $order->id])
            ->assertRedirect(route('tables.index'));

        $this->assertSame(0, Payment::count());
    }

    public function test_le_comptoir_voit_le_bon_et_l_encaisse(): void
    {
        $order = $this->envoyerTournee(3);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)
            ->assertSee('Table 4')
            ->assertSee('Aya')
            ->call('startPayment', $order->id)
            ->assertSet('amountReceived', 3000)
            ->set('amountReceived', 5000)
            ->call('confirmPayment')
            ->assertHasNoErrors()
            ->assertSee('Monnaie à rendre : 2 000 FCFA');

        $payment = Payment::sole();
        $this->assertSame($this->jean->id, $payment->user_id);
        $this->assertFalse($payment->in_sacoche);
        $this->assertSame(2000, $payment->change_given);
        $this->assertTrue($order->fresh()->isPaid());
        // Les clients sont toujours là : la table reste occupée
        $this->assertSame('occupied', $this->table->fresh()->status);
    }

    public function test_l_argent_encaisse_au_comptoir_entre_dans_la_caisse(): void
    {
        $caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->jean->id,
            'opening_amount' => 10000,
            'opened_at' => now()->subHour(),
        ]);

        $order = $this->envoyerTournee(3);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)
            ->call('startPayment', $order->id)
            ->call('confirmPayment');

        $this->assertSame(13000, $caisse->calculateTheoreticalAmount());
        $this->assertSame(0, $this->aya->sacocheBalance());
    }

    public function test_chaque_tournee_fait_un_nouveau_bon_sur_la_meme_table(): void
    {
        $premiere = $this->envoyerTournee(2);
        $this->actingAs($this->jean);
        Livewire::test(Counter::class)->call('startPayment', $premiere->id)->call('confirmPayment');

        $deuxieme = $this->envoyerTournee(1);

        $this->assertNotSame($premiere->id, $deuxieme->id);
        $this->assertSame(1000, $deuxieme->total);
        $this->assertSame('awaiting_payment', $deuxieme->status);
        $this->assertSame(17, $this->biere->fresh()->stock_quantity);
    }

    public function test_un_montant_insuffisant_est_refuse_au_comptoir(): void
    {
        $order = $this->envoyerTournee(3);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)
            ->call('startPayment', $order->id)
            ->set('amountReceived', 2000)
            ->call('confirmPayment')
            ->assertHasErrors('amountReceived');

        $this->assertSame(0, Payment::count());
    }

    public function test_un_bon_ne_peut_pas_etre_encaisse_deux_fois(): void
    {
        $order = $this->envoyerTournee(3);
        $this->actingAs($this->jean);

        $premier = Livewire::test(Counter::class)->call('startPayment', $order->id);
        $second = Livewire::test(Counter::class)->call('startPayment', $order->id);

        $premier->call('confirmPayment');
        $second->call('confirmPayment')->assertSee('Ce bon a déjà été traité.');

        $this->assertSame(1, Payment::count());
    }

    public function test_annuler_un_bon_remet_les_articles_en_stock(): void
    {
        $order = $this->envoyerTournee(3);
        $this->assertSame(17, $this->biere->fresh()->stock_quantity);

        $this->actingAs($this->jean);
        Livewire::test(Counter::class)->call('cancelOrder', $order->id);

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(20, $this->biere->fresh()->stock_quantity);
    }

    public function test_un_bon_paye_ne_peut_pas_etre_annule(): void
    {
        $order = $this->envoyerTournee(3);
        $this->actingAs($this->jean);
        Livewire::test(Counter::class)->call('startPayment', $order->id)->call('confirmPayment');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(Counter::class)->call('cancelOrder', $order->id);
    }

    public function test_la_table_se_libere_seulement_quand_tout_est_regle(): void
    {
        $order = $this->envoyerTournee(3);
        $this->actingAs($this->jean);

        Livewire::test(Counter::class)->call('releaseTable', $this->table->id);
        $this->assertSame('occupied', $this->table->fresh()->status);

        Livewire::test(Counter::class)
            ->call('startPayment', $order->id)
            ->call('confirmPayment')
            ->assertSee('Tables à libérer')
            ->call('releaseTable', $this->table->id);

        $this->assertSame('free', $this->table->fresh()->status);
    }

    public function test_le_comptoir_montre_ce_que_chaque_serveuse_a_apporte(): void
    {
        $this->envoyerTournee(3);
        $this->envoyerTournee(2);
        $this->actingAs($this->jean);

        foreach (Order::where('status', 'awaiting_payment')->get() as $order) {
            Livewire::test(Counter::class)->call('startPayment', $order->id)->call('confirmPayment');
        }

        Livewire::test(Counter::class)
            ->assertSee('Apporté aujourd')
            ->assertSee('2 bons')
            ->assertSee('5 000');
    }

    public function test_impossible_d_encaisser_un_bon_d_un_autre_maquis(): void
    {
        $autre = Order::factory()->awaitingPayment(5000)->create();
        $this->actingAs($this->jean);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(Counter::class)->call('startPayment', $autre->id);
    }

    public function test_une_serveuse_n_a_pas_acces_au_comptoir(): void
    {
        $this->actingAs($this->aya)
            ->get(route('counter'))
            ->assertRedirect(route('tables.index'));
    }

    public function test_en_paiement_a_la_fin_rien_ne_change(): void
    {
        $this->maquis->update(['payment_timing' => 'after']);
        $this->actingAs($this->aya);

        Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->call('addProduct', $this->biere->id)
            ->call('goToPayment')
            ->assertRedirect();

        $order = Order::sole();
        Payment::recordCashPayment($order, $this->aya, 1000);

        // Payé à la fin : la table se libère, l'argent va dans la sacoche
        $this->assertSame('free', $this->table->fresh()->status);
        $this->assertSame(1000, $this->aya->sacocheBalance());

        $this->actingAs($this->jean)->get(route('counter'))->assertRedirect(route('tables.index'));
    }

    public function test_le_patron_choisit_le_moment_du_paiement(): void
    {
        $patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($patron);

        Livewire::test(RestaurantSettings::class)
            ->assertSet('paymentTiming', 'before')
            ->set('paymentTiming', 'after')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($this->maquis->fresh()->paysBeforeService());
    }

    public function test_pas_de_changement_de_mode_avec_des_bons_en_attente(): void
    {
        $this->envoyerTournee();
        $patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($patron);

        Livewire::test(RestaurantSettings::class)
            ->set('paymentTiming', 'after')
            ->call('save')
            ->assertHasErrors('paymentTiming');

        $this->assertTrue($this->maquis->fresh()->paysBeforeService());
    }
}
