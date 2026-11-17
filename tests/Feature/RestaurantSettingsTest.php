<?php

namespace Tests\Feature;

use App\Livewire\Admin\CreateRestaurant;
use App\Livewire\NewOrder;
use App\Livewire\RestaurantSettings;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Les 2 réglages du maquis : mode de service et ardoise.
 */
class RestaurantSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
        $this->owner = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
    }

    public function test_par_defaut_tables_et_vente_rapide_sans_ardoise(): void
    {
        $this->assertSame('both', $this->maquis->fresh()->service_mode);
        $this->assertTrue($this->maquis->usesTables());
        $this->assertTrue($this->maquis->usesQuickSale());
        $this->assertFalse($this->maquis->creditEnabled());
    }

    public function test_le_proprietaire_change_les_reglages(): void
    {
        Livewire::actingAs($this->owner)->test(RestaurantSettings::class)
            ->set('serviceMode', 'quick')
            ->set('creditEnabled', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->maquis->refresh();
        $this->assertSame('quick', $this->maquis->service_mode);
        $this->assertTrue($this->maquis->creditEnabled());
    }

    public function test_un_mode_inconnu_est_refuse(): void
    {
        Livewire::actingAs($this->owner)->test(RestaurantSettings::class)
            ->set('serviceMode', 'drive')
            ->call('save')
            ->assertHasErrors('serviceMode');

        $this->assertSame('both', $this->maquis->fresh()->service_mode);
    }

    public function test_seul_le_proprietaire_accede_aux_parametres(): void
    {
        $this->actingAs($this->owner)->get(route('settings'))->assertOk();

        foreach (['manager', 'cashier', 'server'] as $role) {
            $user = User::factory()->{$role}()->create(['restaurant_id' => $this->maquis->id]);
            $this->actingAs($user)->get(route('settings'))->assertRedirect(route('tables.index'));
        }
    }

    public function test_on_ne_retire_pas_les_tables_tant_qu_une_commande_de_table_n_est_pas_payee(): void
    {
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);
        Order::factory()->awaitingPayment()->create(['restaurant_id' => $this->maquis->id, 'table_id' => $table->id]);

        Livewire::actingAs($this->owner)->test(RestaurantSettings::class)
            ->set('serviceMode', 'quick')
            ->call('save')
            ->assertHasErrors('serviceMode');

        $this->assertSame('both', $this->maquis->fresh()->service_mode);
    }

    // ---------------------------------------------------------------
    // L'application s'adapte au mode
    // ---------------------------------------------------------------

    public function test_en_vente_rapide_le_plan_des_tables_mene_a_la_vente(): void
    {
        $this->maquis->update(['service_mode' => 'quick']);

        $this->actingAs($this->owner)
            ->get(route('tables.index'))
            ->assertRedirect(route('orders.new'));
    }

    public function test_en_vente_rapide_une_commande_sur_table_est_impossible(): void
    {
        $this->maquis->update(['service_mode' => 'quick']);
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);

        Livewire::actingAs($this->owner)
            ->test(NewOrder::class, ['table' => $table->id])
            ->assertRedirect(route('orders.new'));
    }

    public function test_en_service_a_table_la_vente_rapide_est_impossible(): void
    {
        $this->maquis->update(['service_mode' => 'tables']);

        Livewire::actingAs($this->owner)
            ->test(NewOrder::class)
            ->assertRedirect(route('tables.index'));

        $this->actingAs($this->owner)
            ->get(route('tables.index'))
            ->assertOk()
            ->assertDontSee('Vente rapide / à emporter');
    }

    public function test_en_mode_mixte_les_deux_parcours_fonctionnent(): void
    {
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);

        Livewire::actingAs($this->owner)->test(NewOrder::class)->assertNoRedirect();
        Livewire::actingAs($this->owner)->test(NewOrder::class, ['table' => $table->id])->assertNoRedirect();
    }

    public function test_le_menu_s_adapte_au_mode(): void
    {
        $this->maquis->update(['service_mode' => 'quick']);

        $this->actingAs($this->owner)
            ->get(route('dashboard.index'))
            ->assertSee('Vente rapide')
            ->assertDontSee('Tables &amp; commandes', false);
    }

    // ---------------------------------------------------------------
    // À la création par le Super Admin
    // ---------------------------------------------------------------

    public function test_le_super_admin_choisit_les_reglages_a_la_creation(): void
    {
        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();

        Livewire::actingAs($admin)->test(CreateRestaurant::class)
            ->set('name', 'Bar Le Kiosque')
            ->set('restaurantPhone', '0700000001')
            ->set('ownerFirstName', 'Ali')
            ->set('ownerLastName', 'Traoré')
            ->set('ownerPhone', '0700000002')
            ->set('serviceMode', 'quick')
            ->set('creditEnabled', true)
            ->call('save')
            ->assertHasNoErrors();

        $kiosque = Restaurant::where('name', 'Bar Le Kiosque')->sole();
        $this->assertSame('quick', $kiosque->service_mode);
        $this->assertTrue($kiosque->creditEnabled());
    }
}
