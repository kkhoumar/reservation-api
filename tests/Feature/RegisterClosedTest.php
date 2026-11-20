<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\TakePayment;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pas d'encaissement quand la caisse est fermée : sinon l'argent n'est
 * compté dans aucune caisse et peut disparaître sans laisser d'écart.
 */
class RegisterClosedTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $jean;
    private User $aya;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id]);
        $biere = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'selling_price' => 1000, 'stock_quantity' => 10]);

        $this->order = Order::factory()->awaitingPayment(5000)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->aya->id]);
        OrderItem::factory()->create(['order_id' => $this->order->id, 'product_id' => $biere->id, 'quantity' => 5, 'unit_price' => 1000, 'total' => 5000]);
    }

    public function test_impossible_d_encaisser_quand_la_caisse_est_fermee(): void
    {
        $this->actingAs($this->jean);

        try {
            Payment::recordCashPayment($this->order, $this->jean, 5000);
            $this->fail('L\'encaissement aurait dû être refusé.');
        } catch (BusinessRuleException $e) {
            $this->assertSame('La caisse est fermée. Ouvrez la caisse pour encaisser.', $e->getMessage());
        }

        $this->assertSame(0, Payment::count());
        $this->assertSame('awaiting_payment', $this->order->fresh()->status);
    }

    public function test_une_caisse_cloturee_bloque_aussi_les_encaissements(): void
    {
        CashRegister::factory()->closed()->create(['restaurant_id' => $this->maquis->id, 'opened_by' => $this->jean->id]);
        $this->actingAs($this->jean);

        $this->expectException(BusinessRuleException::class);
        Payment::recordCashPayment($this->order, $this->jean, 5000);
    }

    public function test_la_caissiere_ouvre_la_caisse_depuis_l_ecran_puis_encaisse(): void
    {
        $this->actingAs($this->jean);

        Livewire::test(TakePayment::class, ['order' => $this->order->id])
            ->assertSee('La caisse est fermée')
            ->assertDontSee('Valider le paiement')
            ->set('openingAmount', 10000)
            ->call('openRegisterHere')
            ->assertHasNoErrors()
            ->assertSee('Caisse ouverte. Vous pouvez encaisser.')
            ->assertSee('Valider le paiement')
            ->set('amountReceived', 5000)
            ->call('confirmPayment')
            ->assertRedirect(route('payment.success', $this->order->id));

        $register = CashRegister::sole();
        $this->assertSame([$this->jean->id, 10000], [$register->opened_by, $register->opening_amount]);
        $this->assertSame('paid', $this->order->fresh()->status);
        $this->assertSame(1, ActivityLog::where('action', 'register_opened')->count());
    }

    public function test_la_serveuse_ne_peut_pas_ouvrir_la_caisse(): void
    {
        // Paiement à la fin : la serveuse encaisse elle-même à table
        $this->maquis->update(['payment_timing' => 'after']);
        $this->actingAs($this->aya);

        Livewire::test(TakePayment::class, ['order' => $this->order->id])
            ->assertSee("Demandez à la caissière ou au patron d'ouvrir la caisse.", false)
            ->call('openRegisterHere')
            ->assertForbidden();

        $this->assertSame(0, CashRegister::count());
    }
}
