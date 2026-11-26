<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\TablesBoard;
use App\Livewire\TablesManage;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Corrections du parcours client : un nouveau maquis peut créer ses tables,
 * et le patron est guidé pour bien démarrer.
 */
class NewMaquisSetupTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;

    protected function setUp(): void
    {
        parent::setUp();
        $this->maquis = Restaurant::factory()->create(['service_mode' => 'both']);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($this->patron);
    }

    public function test_le_patron_cree_6_tables_d_un_coup(): void
    {
        Livewire::test(TablesManage::class)
            ->set('howMany', 6)->call('createTables')
            ->assertSee('Tables 1 à 6 créées dans « Salle ».', false);

        $this->assertSame(['Table 1', 'Table 2', 'Table 3', 'Table 4', 'Table 5', 'Table 6'], RestaurantTable::orderBy('id')->pluck('label')->all());
        $this->assertSame(1, Zone::count());

        // Les suivantes continuent la numérotation
        Livewire::test(TablesManage::class)->set('howMany', 2)->call('createTables');
        $this->assertSame('Table 8', RestaurantTable::orderByDesc('id')->value('label'));
    }

    public function test_zone_terrasse_renommer_et_supprimer(): void
    {
        $page = Livewire::test(TablesManage::class)
            ->set('newZone', 'Terrasse')->call('addZone')
            ->set('howMany', 2)->call('createTables');
        $terrasse = Zone::where('name', 'Terrasse')->sole();
        $this->assertSame(2, $terrasse->tables()->count());

        $t = $terrasse->tables()->first();
        $page->call('startRename', $t->id)->set('editingLabel', 'VIP')->call('saveRename');
        $this->assertSame('VIP', $t->fresh()->label);

        $page->call('deleteTable', $t->id);
        $this->assertNull(RestaurantTable::find($t->id));
    }

    public function test_une_table_avec_des_ventes_ne_se_supprime_pas(): void
    {
        $table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'label' => 'Table 1']);
        Order::factory()->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->patron->id, 'table_id' => $table->id, 'status' => 'paid']);
        Order::query()->update(['status' => 'paid']);

        Livewire::test(TablesManage::class)->call('deleteTable', $table->id);
        $this->assertNotNull($table->fresh());
    }

    public function test_seuls_le_patron_et_la_gerante_ouvrent_mes_tables(): void
    {
        $serveuse = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'server']);
        $this->actingAs($serveuse)->get(route('tables.manage'))->assertRedirect();
        $this->actingAs($this->patron)->get(route('tables.manage'))->assertOk();
    }

    public function test_un_autre_maquis_ne_touche_pas_mes_tables(): void
    {
        $mine = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);
        $autre = Restaurant::factory()->create();
        $this->actingAs(User::factory()->owner()->create(['restaurant_id' => $autre->id]));

        try {
            Livewire::test(TablesManage::class)->call('deleteTable', $mine->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        }
        $this->assertNotNull($mine->fresh());
    }

    public function test_plan_des_tables_vide_explique_quoi_faire(): void
    {
        Livewire::test(TablesBoard::class)->assertSee('Aucune table pour l\'instant', false)->assertSee('Créer mes tables');

        $serveuse = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'server']);
        $this->actingAs($serveuse);
        Livewire::test(TablesBoard::class)->assertSee("Le patron n'a pas encore créé les tables.", false)->assertDontSee('Créer mes tables');

        // Une table sans zone s'affiche aussi
        RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'zone_id' => null, 'label' => 'Comptoir 1']);
        Livewire::test(TablesBoard::class)->assertSee('Comptoir 1');
    }

    public function test_le_guide_pour_bien_demarrer_suit_le_patron_puis_disparait(): void
    {
        Livewire::test(Dashboard::class)->assertSee('Pour bien démarrer')->assertSee('0 étape sur 4');

        $cat = Category::factory()->create(['restaurant_id' => $this->maquis->id]);
        Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $cat->id]);
        RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);
        Livewire::test(Dashboard::class)->assertSee('2 étapes sur 4');

        User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'server']);
        CashRegister::openFor($this->maquis->id, $this->patron, 0);
        Livewire::test(Dashboard::class)->assertDontSee('Pour bien démarrer');
    }

    public function test_formule_pleine_on_le_dit_avant_le_formulaire(): void
    {
        $plan = \App\Models\Plan::create(['code' => 'mini', 'name' => 'Mini', 'monthly_price' => 5000, 'max_users' => 2, 'includes_auto_report' => false, 'sort_order' => 9, 'is_active' => true]);
        $this->maquis->forceFill(['plan_id' => $plan->id])->save();
        User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'server']);

        Livewire::test(\App\Livewire\UsersManage::class)
            ->assertSee('plus de place')
            ->call('openCreateForm')
            ->assertSet('showForm', false)
            ->assertHasErrors('limit');
    }
}
