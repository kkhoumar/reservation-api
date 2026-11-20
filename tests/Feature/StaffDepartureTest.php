<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\UsersManage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Départ d'un employé : retiré du maquis (plus de connexion, plus dans la
 * liste), jamais effacé, pour que ses ventes et la caisse restent justes.
 */
class StaffDepartureTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $mariam;
    private User $jean;
    private User $aya;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create(['payment_timing' => 'after']);

        $this->openRegister($this->maquis);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->mariam = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Mariam']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya', 'phone' => '0722222222']);
    }

    public function test_le_patron_retire_une_serveuse_qui_part(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)
            ->call('removeUser', $this->aya->id)
            ->assertSee('a été retiré(e) du maquis')
            ->assertSee('Anciens employés (1)');

        $aya = $this->aya->fresh();
        $this->assertTrue($aya->hasLeft());
        $this->assertSame('inactive', $aya->status);
        $this->assertFalse($this->maquis->hasActiveServers());
    }

    public function test_une_personne_retiree_ne_peut_plus_se_connecter(): void
    {
        $this->aya->forceFill(['status' => 'inactive', 'left_at' => now()])->save();

        $this->assertFalse(auth()->validate(['phone' => '0722222222', 'password' => 'password', 'status' => 'active']));
    }

    public function test_son_historique_de_ventes_est_conserve(): void
    {
        $order = Order::factory()->awaitingPayment(3000)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->jean->id]);
        Payment::recordCashPayment($order, $this->jean, 3000);
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)->call('removeUser', $this->jean->id);

        $this->assertSame(1, Payment::count());
        $this->assertSame('Jean', Payment::sole()->cashier->first_name);
    }

    public function test_on_ne_peut_jamais_effacer_quelqu_un_qui_a_un_historique(): void
    {
        Order::factory()->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->aya->id]);

        try {
            $this->aya->delete();
            $this->fail('Un employé avec un historique a été effacé.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('retirez-le du maquis', $e->getMessage());
        }

        $this->assertSame(1, Order::withoutGlobalScopes()->count());
        $this->assertNotNull($this->aya->fresh());
    }

    public function test_une_serveuse_qui_doit_de_l_argent_ne_peut_pas_partir_avant_de_verser(): void
    {
        $order = Order::factory()->awaitingPayment(4000)->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->aya->id]);
        Payment::recordCashPayment($order, $this->aya, 4000); // dans sa sacoche
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)
            ->call('removeUser', $this->aya->id)
            ->assertSee('doit encore verser 4 000 FCFA');

        $this->assertFalse($this->aya->fresh()->hasLeft());
    }

    public function test_la_gerante_retire_un_caissier_mais_pas_un_gerant_ni_le_patron(): void
    {
        $autreGerant = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($this->mariam);

        Livewire::test(UsersManage::class)->call('removeUser', $this->jean->id);
        $this->assertTrue($this->jean->fresh()->hasLeft());

        Livewire::test(UsersManage::class)->call('removeUser', $autreGerant->id)->assertForbidden();
        Livewire::test(UsersManage::class)->call('removeUser', $this->patron->id)->assertForbidden();
    }

    public function test_le_patron_retire_une_gerante_mais_ni_lui_meme_ni_un_autre_patron(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)->call('removeUser', $this->mariam->id);
        $this->assertTrue($this->mariam->fresh()->hasLeft());

        Livewire::test(UsersManage::class)->call('removeUser', $this->patron->id)->assertForbidden();
    }

    public function test_impossible_de_retirer_l_employe_d_un_autre_maquis(): void
    {
        $autre = User::factory()->server()->create();
        $this->actingAs($this->patron);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(UsersManage::class)->call('removeUser', $autre->id);
    }

    public function test_reintegrer_un_ancien_employe(): void
    {
        $this->aya->forceFill(['status' => 'inactive', 'left_at' => now()])->save();
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)
            ->call('restoreUser', $this->aya->id)
            ->assertSee('fait de nouveau partie de l');

        $aya = $this->aya->fresh();
        $this->assertFalse($aya->hasLeft());
        $this->assertSame('active', $aya->status);
    }

    public function test_recreer_un_ancien_employe_propose_de_le_reintegrer(): void
    {
        $this->aya->forceFill(['status' => 'inactive', 'left_at' => now()])->save();
        $this->actingAs($this->patron);

        Livewire::test(UsersManage::class)
            ->call('openCreateForm')
            ->set('firstName', 'Aya')
            ->set('lastName', 'Konan')
            ->set('phone', '07 22 22 22 22')
            ->set('password', 'motdepasse')
            ->call('save')
            ->assertHasErrors('phone')
            ->assertSee('réintégrez-le');

        $this->assertSame(1, User::where('phone', '0722222222')->count());
    }
}
