<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\Sacoches;
use App\Models\CashRegister;
use App\Models\CashRemittance;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La sacoche : l'argent encaissé par une serveuse reste sur elle jusqu'au
 * versement à la caisse. Le patron sait toujours qui doit combien.
 */
class SacocheTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $caissier;
    private User $aya;
    private CashRegister $caisse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->caissier = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya']);

        $this->caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->caissier->id,
            'opening_amount' => 10000,
            'opened_at' => now()->subHour(),
        ]);
    }

    private function encaisser(User $par, int $total, int $recu): Payment
    {
        $order = Order::factory()->awaitingPayment($total)->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $this->aya->id,
        ]);

        return Payment::recordCashPayment($order, $par, $recu);
    }

    public function test_un_paiement_encaisse_par_une_serveuse_va_dans_sa_sacoche(): void
    {
        $payment = $this->encaisser($this->aya, 3000, 5000);

        $this->assertTrue($payment->in_sacoche);
        // Elle a rendu 2 000 de monnaie : il lui reste 3 000 à verser
        $this->assertSame(3000, $this->aya->sacocheBalance());
        // La caisse ne compte pas encore cet argent
        $this->assertSame(10000, $this->caisse->calculateTheoreticalAmount());
    }

    public function test_un_paiement_encaisse_a_la_caisse_ne_passe_pas_par_la_sacoche(): void
    {
        $payment = $this->encaisser($this->caissier, 3000, 5000);

        $this->assertFalse($payment->in_sacoche);
        $this->assertSame(0, $this->aya->sacocheBalance());
        $this->assertSame(13000, $this->caisse->calculateTheoreticalAmount());
    }

    public function test_le_versement_fait_passer_l_argent_de_la_sacoche_a_la_caisse(): void
    {
        $this->encaisser($this->aya, 3000, 3000);
        $this->encaisser($this->aya, 2000, 2000);

        $this->actingAs($this->caissier);
        $remittance = CashRemittance::record($this->aya, $this->caissier, 4000);

        $this->assertSame($this->caisse->id, $remittance->cash_register_id);
        $this->assertSame(1000, $this->aya->sacocheBalance());

        $breakdown = $this->caisse->breakdown();
        $this->assertSame(1, $breakdown['remittances_count']);
        $this->assertSame(4000, $breakdown['remittances_total']);
        $this->assertSame(14000, $breakdown['theoretical']);
    }

    public function test_la_cloture_tient_compte_des_versements(): void
    {
        $this->encaisser($this->aya, 5000, 5000);
        $this->actingAs($this->caissier);
        CashRemittance::record($this->aya, $this->caissier, 5000);

        $this->caisse->close(15000, $this->caissier->id);

        $this->assertSame(15000, $this->caisse->fresh()->theoretical_amount);
        $this->assertSame(0, $this->caisse->fresh()->discrepancy);
    }

    public function test_impossible_de_verser_plus_que_le_contenu_de_la_sacoche(): void
    {
        $this->encaisser($this->aya, 3000, 3000);
        $this->actingAs($this->caissier);

        $this->expectException(BusinessRuleException::class);
        CashRemittance::record($this->aya, $this->caissier, 3500);
    }

    public function test_un_versement_nul_ou_negatif_est_refuse(): void
    {
        $this->encaisser($this->aya, 3000, 3000);
        $this->actingAs($this->caissier);

        foreach ([0, -500] as $montant) {
            try {
                CashRemittance::record($this->aya, $this->caissier, $montant);
                $this->fail("Versement de $montant accepté.");
            } catch (BusinessRuleException) {
            }
        }

        $this->assertSame(0, CashRemittance::count());
    }

    public function test_pas_de_versement_sans_caisse_ouverte(): void
    {
        $this->encaisser($this->aya, 3000, 3000);
        $this->caisse->close(10000, $this->caissier->id);
        $this->actingAs($this->caissier);

        try {
            CashRemittance::record($this->aya, $this->caissier, 3000);
            $this->fail('Versement accepté sans caisse ouverte.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('Ouvrez', $e->getMessage());
        }

        $this->assertSame(3000, $this->aya->sacocheBalance());
    }

    public function test_seule_une_serveuse_a_une_sacoche(): void
    {
        $this->actingAs($this->caissier);

        $this->expectException(BusinessRuleException::class);
        CashRemittance::record($this->caissier, $this->caissier, 1000);
    }

    public function test_l_ecran_sacoches_recoit_un_versement(): void
    {
        $this->encaisser($this->aya, 3000, 3000);
        $this->actingAs($this->caissier);

        Livewire::test(Sacoches::class)
            ->assertSee('Aya')
            ->call('startRemittance', $this->aya->id)
            ->assertSet('amount', 3000)
            ->set('amount', 2000)
            ->call('confirmRemittance')
            ->assertHasNoErrors()
            ->assertSet('serverId', null);

        $this->assertSame(1000, $this->aya->sacocheBalance());
        $this->assertSame($this->caissier->id, CashRemittance::sole()->received_by);
    }

    public function test_l_ecran_affiche_l_erreur_si_le_montant_depasse_la_sacoche(): void
    {
        $this->encaisser($this->aya, 3000, 3000);
        $this->actingAs($this->caissier);

        Livewire::test(Sacoches::class)
            ->call('startRemittance', $this->aya->id)
            ->set('amount', 9000)
            ->call('confirmRemittance')
            ->assertHasErrors('amount');

        $this->assertSame(0, CashRemittance::count());
    }

    public function test_impossible_de_recevoir_le_versement_d_une_serveuse_d_un_autre_maquis(): void
    {
        $autre = User::factory()->server()->create();
        $this->actingAs($this->caissier);

        // En production : page 404
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(Sacoches::class)->call('startRemittance', $autre->id);
    }

    public function test_une_serveuse_n_a_pas_acces_a_l_ecran_des_sacoches(): void
    {
        $this->actingAs($this->aya)
            ->get(route('sacoches'))
            ->assertRedirect(route('tables.index'));
    }

    public function test_la_serveuse_voit_le_contenu_de_sa_sacoche(): void
    {
        $this->encaisser($this->aya, 3000, 3000);

        $this->actingAs($this->aya)
            ->get(route('tables.index'))
            ->assertOk()
            ->assertSee('Ma sacoche : 3 000 FCFA');
    }

    public function test_la_caisse_signale_les_sacoches_non_videes(): void
    {
        $this->encaisser($this->aya, 3000, 3000);

        $this->actingAs($this->caissier)
            ->get(route('cash-register.manage'))
            ->assertOk()
            ->assertSee('Sacoches non vidées');
    }
}
