<?php

namespace Tests\Feature;

use App\Livewire\Admin\CreateRestaurant;
use App\Livewire\Admin\RestaurantsList;
use App\Livewire\DemoRequestForm;
use App\Models\DemoRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Plus d'inscription libre : le prospect demande une démo,
 * le Super Admin crée son maquis.
 */
class DemoRequestTest extends TestCase
{
    use RefreshDatabase;

    private function demander(array $overrides = [])
    {
        $component = Livewire::test(DemoRequestForm::class);

        foreach (array_merge([
            'contactName' => 'Koffi Yao',
            'restaurantName' => 'Maquis Le Baobab',
            'phone' => '07 88 99 00 11',
            'commune' => 'Marcory',
        ], $overrides) as $field => $value) {
            $component->set($field, $value);
        }

        return $component->call('submit');
    }

    public function test_l_inscription_libre_n_existe_plus(): void
    {
        $this->get('/register')->assertRedirect('/demande-demo');
        $this->get(route('demo.request'))->assertOk()->assertSee('Demander une démo');
    }

    public function test_un_prospect_demande_une_demo(): void
    {
        $this->demander()->assertHasNoErrors()->assertSet('sent', true);

        $demo = DemoRequest::sole();
        $this->assertSame('0788990011', $demo->phone);
        $this->assertSame('new', $demo->status);

        // Aucun compte ni maquis n'est créé
        $this->assertSame(0, Restaurant::count());
        $this->assertSame(0, User::count());
    }

    public function test_une_demande_en_double_n_est_pas_enregistree_deux_fois(): void
    {
        $this->demander();
        $this->demander(['phone' => '+225 0788990011']);

        $this->assertSame(1, DemoRequest::count());
    }

    public function test_un_robot_qui_remplit_le_champ_piege_n_enregistre_rien(): void
    {
        $this->demander(['website' => 'http://spam.example'])->assertSet('sent', true);

        $this->assertSame(0, DemoRequest::count());
    }

    public function test_pas_plus_de_3_demandes_par_heure_depuis_la_meme_connexion(): void
    {
        foreach (['0711000001', '0711000002', '0711000003'] as $phone) {
            $this->demander(['phone' => $phone])->assertHasNoErrors();
        }

        $this->demander(['phone' => '0711000004'])->assertHasErrors('phone');

        $this->assertSame(3, DemoRequest::count());
    }

    public function test_un_numero_invalide_est_refuse(): void
    {
        $this->demander(['phone' => '123'])->assertHasErrors('phone');

        $this->assertSame(0, DemoRequest::count());
    }

    // ---------------------------------------------------------------
    // Côté Super Admin
    // ---------------------------------------------------------------

    private function admin(): User
    {
        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin;
    }

    public function test_le_super_admin_voit_les_demandes_en_attente(): void
    {
        DemoRequest::factory()->create(['restaurant_name' => 'Maquis Le Baobab']);
        DemoRequest::factory()->create(['restaurant_name' => 'Maquis Classé'])->forceFill(['status' => 'rejected'])->save();

        Livewire::actingAs($this->admin())->test(RestaurantsList::class)
            ->assertSee('Maquis Le Baobab')
            ->assertDontSee('Maquis Classé');
    }

    public function test_creer_le_maquis_depuis_une_demande(): void
    {
        $demo = DemoRequest::factory()->create([
            'contact_name' => 'Koffi Yao',
            'restaurant_name' => 'Maquis Le Baobab',
            'phone' => '0788990011',
            'commune' => 'Marcory',
        ]);

        Livewire::actingAs($this->admin())
            ->withQueryParams(['demande' => $demo->id])
            ->test(CreateRestaurant::class)
            ->assertSet('name', 'Maquis Le Baobab')
            ->assertSet('ownerFirstName', 'Koffi')
            ->assertSet('ownerLastName', 'Yao')
            ->assertSet('ownerPhone', '0788990011')
            ->call('save')
            ->assertHasNoErrors();

        $demo->refresh();
        $this->assertSame('converted', $demo->status);
        $this->assertSame(Restaurant::sole()->id, $demo->restaurant_id);
        $this->assertSame('owner', User::where('phone', '0788990011')->sole()->role);
    }

    public function test_classer_une_demande_sans_suite(): void
    {
        $demo = DemoRequest::factory()->create();

        Livewire::actingAs($this->admin())->test(RestaurantsList::class)
            ->call('setDemoStatus', $demo->id, 'rejected');

        $this->assertSame('rejected', $demo->fresh()->status);
    }

    public function test_on_ne_peut_pas_forcer_un_statut_client_cree_a_la_main(): void
    {
        $demo = DemoRequest::factory()->create();

        Livewire::actingAs($this->admin())->test(RestaurantsList::class)
            ->call('setDemoStatus', $demo->id, 'converted');

        $this->assertSame('new', $demo->fresh()->status);
    }
}
