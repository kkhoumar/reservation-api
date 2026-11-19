<?php

namespace Tests\Feature;

use App\Livewire\CashRegisterManage;
use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Écran Caisse explicite : montant attendu dans le tiroir, calcul expliqué,
 * ce qui n'est pas dans le tiroir, journal des mouvements, billetage.
 */
class CashRegisterScreenTest extends TestCase
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
            'opened_at' => now()->subHours(2),
        ]);
    }

    private function bon(int $total): Order
    {
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'label' => 'Table 4']);

        return Order::factory()->awaitingPayment($total)->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $this->aya->id,
            'table_id' => $table->id,
        ]);
    }

    /** 3 000 espèces (reçu 5 000), 6 000 Wave, 4 000 à crédit, 1 500 de dépense. */
    private function soiree(): void
    {
        Payment::recordCashPayment($this->bon(3000), $this->jean, 5000);
        Payment::recordPayment($this->bon(6000), $this->jean, 0, 'wave', 6000, 'TX-1');
        $konan = Customer::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'M. Konan']);
        CreditTransaction::chargeOrder($this->bon(4000), $konan, $this->patron);
        Expense::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'cash_register_id' => $this->caisse->id,
            'user_id' => $this->jean->id,
            'amount' => 1500,
            'description' => 'Glace',
            'expense_date' => now(),
        ]);
    }

    public function test_le_journal_liste_chaque_mouvement(): void
    {
        $this->soiree();
        $journal = $this->caisse->journal();

        $cash = $journal->firstWhere('kind', 'sale');
        $this->assertSame(3000, $cash->amount);
        $this->assertTrue($cash->drawer);
        $this->assertStringContainsString('reçu 5 000, rendu 2 000', $cash->detail);
        $this->assertStringContainsString('servi par Aya', $cash->detail);

        $wave = $journal->firstWhere('kind', 'mobile');
        $this->assertSame([6000, false, 'Vente Wave'], [$wave->amount, $wave->drawer, $wave->label]);

        $credit = $journal->firstWhere('kind', 'credit');
        $this->assertSame([4000, false], [$credit->amount, $credit->drawer]);

        $expense = $journal->firstWhere('kind', 'expense');
        $this->assertSame([-1500, true], [$expense->amount, $expense->drawer]);

        // Le tiroir : 10 000 + 3 000 - 1 500
        $this->assertSame(11500, $journal->where('drawer', true)->sum('amount') + 10000);
        $this->assertSame(11500, $this->caisse->calculateTheoreticalAmount());
    }

    public function test_l_ecran_explique_le_montant_et_ce_qui_n_est_pas_dans_le_tiroir(): void
    {
        $this->soiree();
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->assertSee('Argent qui doit être dans le tiroir maintenant')
            ->assertSee('11 500')
            ->assertSee('Comment on arrive à ce montant')
            ->assertSee('reçu 5 000, monnaie rendue 2 000')
            ->assertSee("Ce qui n'est PAS dans le tiroir", false)
            ->assertSee('Wave : 6 000')
            ->assertSee('Vendu à crédit')
            ->assertSee('Journal de la caisse')
            ->assertSee('Vente à crédit — M. Konan')
            ->assertSee('Dépense — Glace')
            ->assertSee('hors tiroir');
    }

    public function test_le_bilan_d_une_caisse_encore_ouverte_renvoie_a_la_caisse(): void
    {
        $this->soiree();

        // Avant : erreur 500 (« format() on null ») en ouvrant ce lien
        $this->actingAs($this->jean)
            ->get(route('cash-register.closed', $this->caisse->id))
            ->assertRedirect(route('cash-register.manage'));
    }

    public function test_cloture_par_billetage(): void
    {
        $this->soiree();
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->set('showCloseForm', true)
            ->set('counts.b10000', 1)
            ->set('counts.b1000', 1)
            ->set('counts.p500', 1)
            ->assertSet('countedAmount', 11500)
            ->assertSee('La caisse est juste.')
            ->call('confirmClose')
            ->assertRedirect(route('cash-register.closed', $this->caisse->id));

        $closed = $this->caisse->fresh();
        $this->assertSame(11500, $closed->counted_amount);
        $this->assertSame(0, $closed->discrepancy);
        $this->assertSame(['b10000' => 1, 'b1000' => 1, 'p500' => 1], $closed->cash_count);
    }

    public function test_le_billetage_fait_foi_meme_si_le_total_est_modifie(): void
    {
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->set('counts.b5000', 2)
            ->set('countedAmount', 999999)
            ->call('confirmClose');

        $this->assertSame(10000, $this->caisse->fresh()->counted_amount);
    }

    public function test_un_nombre_de_billets_negatif_est_refuse(): void
    {
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->set('counts.b5000', -3)
            ->call('confirmClose')
            ->assertHasErrors('counts.b5000');

        $this->assertTrue($this->caisse->fresh()->isOpen());
    }

    public function test_l_ecart_est_explique_en_clair(): void
    {
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->set('showCloseForm', true)
            ->set('directEntry', true)
            ->set('countedAmount', 9000)
            ->assertSee('Il manque 1 000 FCFA dans le tiroir.')
            ->set('countedAmount', 10200)
            ->assertSee('Il y a 200 FCFA de trop dans le tiroir.');
    }

    public function test_le_total_du_billetage(): void
    {
        $this->assertSame(17775, CashRegister::countTotal([
            'b10000' => 1, 'b5000' => 1, 'b2000' => 1, 'p500' => 1, 'p250' => 1, 'p25' => 1,
            'inconnu' => 50, 'b1000' => -4,
        ]));
    }

    public function test_caisse_fermee_rappelle_la_derniere_cloture(): void
    {
        $this->caisse->close(12000, $this->jean->id);
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->assertSee('La caisse est fermée')
            ->assertSeeHtml('Dernière clôture : <strong>12 000 FCFA</strong>');
    }
}
