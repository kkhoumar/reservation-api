<?php

namespace Tests\Feature;

use App\Livewire\CashRegisterManage;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\RegisterReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * « Argent du tiroir » : ajouter de la monnaie oubliée à l'ouverture,
 * ou retirer l'argent que le patron récupère, sans faux écart à la clôture.
 */
class CashMovementTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $caissier;
    private CashRegister $caisse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Koffi']);
        $this->caissier = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'cashier', 'first_name' => 'Jean']);
        $this->actingAs($this->caissier);
        $this->caisse = CashRegister::openFor($this->maquis->id, $this->caissier, 0);
    }

    private function move(string $type, int $amount, string $reason, string $note = '')
    {
        return Livewire::test(CashRegisterManage::class)
            ->call('openMove', $type)
            ->set('moveType', $type)
            ->set('moveAmount', $amount)
            ->set('moveReason', $reason)
            ->set('moveNote', $note)
            ->call('saveMovement');
    }

    public function test_la_journee_de_koffi_tombe_juste(): void
    {
        // 11 h : Koffi apporte 10 000 F de monnaie
        $this->actingAs($this->patron);
        $this->move('in', 10000, 'owner_brought')->assertHasNoErrors()->assertSee('10 000 F ajoutés au tiroir.');
        $this->assertSame(10000, $this->caisse->fresh()->calculateTheoreticalAmount());

        // 20 h : Koffi récupère 4 000 F
        $this->move('out', 4000, 'owner_took')->assertHasNoErrors()->assertSee('4 000 F retirés du tiroir.');
        $this->assertSame(6000, $this->caisse->fresh()->calculateTheoreticalAmount());

        // 23 h : on compte 6 000 F → tout est juste
        $caisse = $this->caisse->fresh();
        $caisse->close(6000, $this->caissier->id);
        $this->assertSame(0, (int) $caisse->fresh()->discrepancy);

        // Visible dans le rapport du soir et dans le Journal
        $text = (new RegisterReport($caisse->fresh()))->toWhatsappText();
        $this->assertStringContainsString('Argent ajouté au tiroir : +10 000', $text);
        $this->assertStringContainsString('Argent retiré du tiroir : −4 000', $text);
        $this->assertSame(2, DB::table('activity_logs')->whereIn('action', ['cash_in', 'cash_out'])->count());
    }

    public function test_le_caissier_peut_ajouter_mais_jamais_retirer(): void
    {
        $this->move('in', 5000, 'forgotten')->assertHasNoErrors();

        $this->move('out', 1000, 'owner_took');
        $this->assertSame(0, CashMovement::where('type', 'out')->count());
        $this->assertSame(5000, $this->caisse->fresh()->calculateTheoreticalAmount());

        Livewire::test(CashRegisterManage::class)->call('openMove', 'in')->assertDontSee('Je retire');
    }

    public function test_la_serveuse_ne_peut_rien_faire(): void
    {
        $serveuse = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'server']);
        $this->actingAs($serveuse);

        $this->assertFalse(CashMovement::canAdd($serveuse));
        $this->assertFalse(CashMovement::canRemove($serveuse));
    }

    public function test_on_ne_retire_pas_plus_que_ce_qu_il_y_a_dans_le_tiroir(): void
    {
        $this->actingAs($this->patron);
        $this->move('in', 3000, 'forgotten');

        $this->move('out', 5000, 'owner_took')->assertHasErrors('moveAmount');
        $this->assertSame(3000, $this->caisse->fresh()->calculateTheoreticalAmount());
    }

    public function test_il_faut_une_raison_et_un_mot_pour_autre(): void
    {
        $this->move('in', 2000, '')->assertHasErrors('moveReason');
        $this->move('in', 2000, 'other')->assertHasErrors('moveNote');
        $this->move('in', 2000, 'other', 'Échange avec le voisin')->assertHasNoErrors();
        $this->assertSame('Échange avec le voisin', CashMovement::sole()->note);
    }

    public function test_caisse_fermee_rien_n_est_enregistre(): void
    {
        $this->caisse->close(0, $this->caissier->id);

        $this->move('in', 1000, 'forgotten');
        $this->assertSame(0, CashMovement::count());
    }

    public function test_un_autre_maquis_ne_voit_pas_ces_mouvements(): void
    {
        $this->move('in', 7000, 'forgotten');

        $autre = Restaurant::factory()->create();
        $this->actingAs(User::factory()->owner()->create(['restaurant_id' => $autre->id]));
        $this->assertSame(0, CashMovement::count());
    }

    public function test_corriger_le_fonds_de_depart_tant_que_rien_ne_s_est_passe(): void
    {
        Livewire::test(CashRegisterManage::class)
            ->set('newOpening', 50000)->call('fixOpening')
            ->assertSee('Fonds de départ corrigé : 50 000 F.');
        $this->assertSame(50000, (int) $this->caisse->fresh()->opening_amount);

        // Après un mouvement : plus possible, on passe par « Argent du tiroir »
        $this->move('in', 1000, 'forgotten');
        Livewire::test(CashRegisterManage::class)->set('newOpening', 1)->call('fixOpening');
        $this->assertSame(50000, (int) $this->caisse->fresh()->opening_amount);
    }
}
