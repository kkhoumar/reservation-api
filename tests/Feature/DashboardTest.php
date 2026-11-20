<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Expense;
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
 * Tableau de bord : en ce moment (caisse, comptoir, crédits), et sur la période
 * (chiffre d'affaires comparé, qui vend, ce qui se vend, ce qu'il faut surveiller).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;
    private User $aya;
    private Product $biere;

    protected function setUp(): void
    {
        parent::setUp();

        // Mercredi 18 novembre 2026, 20 h
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(20, 0));

        $this->maquis = Restaurant::factory()->create(['credit_enabled' => true]);

        $this->openRegister($this->maquis);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Koffi']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya']);
        $this->biere = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Bière', 'selling_price' => 1000]);
    }

    private function bon(int $qty): Order
    {
        $order = Order::factory()->awaitingPayment($qty * 1000)->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $this->aya->id,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id, 'product_id' => $this->biere->id,
            'quantity' => $qty, 'unit_price' => 1000, 'total' => $qty * 1000,
        ]);

        return $order;
    }

    private function vendre(int $qty, $at = null): void
    {
        $payment = Payment::recordCashPayment($this->bon($qty), $this->jean, $qty * 1000);

        if ($at) {
            $payment->update(['paid_at' => $at]);
        }
    }

    public function test_le_chiffre_d_affaires_est_compare_a_hier_a_la_meme_heure(): void
    {
        $this->vendre(4, now()->subDay()->setTime(19, 0));  // hier 19 h : compté
        $this->vendre(10, now()->subDay()->setTime(22, 0)); // hier 22 h : pas encore « à la même heure »
        $this->vendre(6);

        $this->actingAs($this->patron);

        Livewire::test(Dashboard::class)
            ->assertViewHas('revenue', 6000)
            ->assertViewHas('previousRevenue', 4000)
            ->assertSee('▲ +50 %')
            ->assertSee('hier à la même heure');
    }

    public function test_la_periode_semaine_et_mois(): void
    {
        $this->vendre(2, now()->subDays(2));   // lundi : dans la semaine
        $this->vendre(3, now()->subDays(10));  // le 8 : dans le mois
        $this->vendre(1);

        $this->actingAs($this->patron);

        Livewire::test(Dashboard::class)
            ->call('setPeriod', 'week')
            ->assertViewHas('revenue', 3000)
            ->call('setPeriod', 'month')
            ->assertViewHas('revenue', 6000)
            ->call('setPeriod', 'nimporte')
            ->assertSet('period', 'today');
    }

    public function test_panier_moyen_et_reste_apres_depenses(): void
    {
        $this->vendre(3);
        $this->vendre(5);
        $konan = Customer::factory()->create(['restaurant_id' => $this->maquis->id]);
        CreditTransaction::chargeOrder($this->bon(4), $konan, $this->patron);
        Expense::factory()->create([
            'restaurant_id' => $this->maquis->id, 'amount' => 2500, 'category' => 'transport', 'expense_date' => now(),
        ]);

        $this->actingAs($this->patron);

        Livewire::test(Dashboard::class)
            ->assertViewHas('revenue', 12000)
            ->assertViewHas('orderCount', 3)
            ->assertViewHas('averageTicket', 4000)
            ->assertViewHas('expenses', 2500)
            // 8 000 rentrés (le crédit n'est pas encore payé) - 2 500
            ->assertViewHas('netCash', 5500)
            ->assertSee('Transport : 2 500');
    }

    public function test_en_ce_moment_caisse_comptoir_et_credits(): void
    {
        CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->jean->id,
            'opening_amount' => 10000,
            'opened_at' => now()->subHours(3),
        ]);
        $this->vendre(2);
        $this->bon(3); // en attente au comptoir
        $konan = Customer::factory()->create(['restaurant_id' => $this->maquis->id]);
        CreditTransaction::chargeOrder($this->bon(4), $konan, $this->patron);

        $this->actingAs($this->patron);

        Livewire::test(Dashboard::class)
            ->assertViewHas('drawerAmount', 12000)
            ->assertSee('ouverte par Jean')
            ->assertViewHas('pendingCount', 1)
            ->assertSee('3 000 FCFA à encaisser')
            ->assertViewHas('creditDue', 4000)
            ->assertSee('Crédits à récupérer');
    }

    public function test_qui_vend_ce_qui_se_vend_et_ce_qu_il_faut_surveiller(): void
    {
        $this->vendre(5);
        $offert = $this->bon(2);
        $offert->offerItem($offert->items()->sole()->id, 1, $this->patron, 'Bon client');
        Payment::recordCashPayment($offert->fresh(), $this->jean, 1000);
        $this->bon(3)->cancel();
        $register = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id, 'opened_by' => $this->jean->id,
            'opening_amount' => 0, 'opened_at' => now()->subHours(4),
        ]);
        $register->close(5000, $this->jean->id, 'Erreur');

        $this->actingAs($this->patron);

        Livewire::test(Dashboard::class)
            ->assertSee('Qui a vendu')
            ->assertSee('Aya')
            ->assertSee('2 bons')
            ->assertSee('Bière')
            ->assertSee('× 7') // 5 + 1 payée + 1 offerte : les offertes sortent aussi du frigo
            ->assertSee('1 × Bière offert par Koffi')
            ->assertViewHas('closingsWithGap', 1)
            ->assertSee('Bons annulés');
    }

    public function test_la_caissiere_n_a_pas_acces_au_tableau_de_bord(): void
    {
        $this->actingAs($this->jean)
            ->get(route('dashboard.index'))
            ->assertRedirect(route('tables.index'));
    }
}
