<?php

namespace Tests\Feature;

use App\Livewire\Admin\RestaurantsList;
use App\Livewire\Login;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin (propriétaire de la plateforme) et verrouillage des maquis.
 */
class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Restaurant $maquisA;
    private Restaurant $maquisB;
    private User $ownerA;
    private Order $orderB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['restaurant_id' => null]);
        $this->admin->forceFill(['is_platform_admin' => true])->save();

        $this->maquisA = Restaurant::factory()->create(['name' => 'Maquis A']);
        $this->ownerA = User::factory()->owner()->create(['restaurant_id' => $this->maquisA->id]);
        Product::factory()->count(2)->create(['restaurant_id' => $this->maquisA->id]);

        $this->maquisB = Restaurant::factory()->create(['name' => 'Maquis B']);
        Product::factory()->count(3)->create(['restaurant_id' => $this->maquisB->id]);
        $this->orderB = Order::factory()->awaitingPayment()->create(['restaurant_id' => $this->maquisB->id]);
    }

    private function enterAs(User $admin, Restaurant $restaurant): void
    {
        $this->actingAs($admin)->withSession([User::ADMIN_RESTAURANT_SESSION_KEY => $restaurant->id]);
    }

    // ---------------------------------------------------------------
    // Accès du Super Admin
    // ---------------------------------------------------------------

    public function test_le_super_admin_voit_les_donnees_de_tous_les_maquis(): void
    {
        $this->actingAs($this->admin);

        $this->assertSame(5, Product::count());
    }

    public function test_le_super_admin_entre_dans_un_maquis_et_ne_voit_que_lui(): void
    {
        $this->actingAs($this->admin);
        session([User::ADMIN_RESTAURANT_SESSION_KEY => $this->maquisB->id]);

        $this->assertSame(3, Product::count());
    }

    public function test_le_super_admin_peut_ouvrir_les_ecrans_du_maquis_choisi(): void
    {
        $this->enterAs($this->admin, $this->maquisB);

        $this->get(route('payment.take', $this->orderB->id))->assertOk();
        $this->get(route('dashboard.index'))->assertOk();
    }

    public function test_le_super_admin_doit_choisir_un_maquis_avant_d_ouvrir_ses_ecrans(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tables.index'))
            ->assertRedirect(route('admin.restaurants'));
    }

    public function test_entrer_dans_un_maquis_depuis_la_liste(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RestaurantsList::class)
            ->call('enterRestaurant', $this->maquisB->id)
            ->assertRedirect(route('dashboard.index'));

        $this->assertSame($this->maquisB->id, session(User::ADMIN_RESTAURANT_SESSION_KEY));
    }

    public function test_quitter_un_maquis(): void
    {
        $this->enterAs($this->admin, $this->maquisB);

        $this->post(route('admin.leave-restaurant'))
            ->assertRedirect(route('admin.restaurants'))
            ->assertSessionMissing(User::ADMIN_RESTAURANT_SESSION_KEY);
    }

    public function test_le_super_admin_arrive_sur_la_liste_des_maquis_apres_connexion(): void
    {
        $this->admin->update(['password' => 'secret-admin-123']);

        Livewire::test(Login::class)
            ->set('phone', $this->admin->phone)
            ->set('password', 'secret-admin-123')
            ->call('authenticate')
            ->assertRedirect(route('admin.restaurants'));
    }

    public function test_un_patron_n_a_pas_acces_a_l_espace_super_admin(): void
    {
        $this->actingAs($this->ownerA)
            ->get(route('admin.restaurants'))
            ->assertRedirect(route('tables.index'));
    }

    public function test_is_platform_admin_n_est_pas_assignable_en_masse(): void
    {
        $this->ownerA->update(['is_platform_admin' => true]);

        $this->assertFalse($this->ownerA->fresh()->isPlatformAdmin());
    }

    // ---------------------------------------------------------------
    // Verrouillage d'un maquis (non-paiement)
    // ---------------------------------------------------------------

    public function test_le_super_admin_verrouille_un_maquis(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RestaurantsList::class)->call('toggleStatus', $this->maquisA->id);

        $this->assertSame('suspended', $this->maquisA->fresh()->subscription_status);
    }

    public function test_le_super_admin_reactive_un_maquis(): void
    {
        $this->maquisA->update(['subscription_status' => 'suspended']);
        $this->actingAs($this->admin);

        Livewire::test(RestaurantsList::class)->call('toggleStatus', $this->maquisA->id);

        $this->assertSame('active', $this->maquisA->fresh()->subscription_status);
    }

    public function test_le_super_admin_reactive_en_un_clic_un_maquis_en_fin_d_essai(): void
    {
        $this->maquisA->update(['subscription_status' => 'trial', 'trial_ends_at' => now()->subDay()]);
        $this->actingAs($this->admin);

        Livewire::test(RestaurantsList::class)
            ->assertSee('Réactiver')
            ->call('toggleStatus', $this->maquisA->id);

        $this->assertSame('active', $this->maquisA->fresh()->subscription_status);
        $this->assertFalse($this->maquisA->fresh()->isLocked());
    }

    public function test_le_personnel_d_un_maquis_verrouille_est_bloque(): void
    {
        $this->maquisA->update(['subscription_status' => 'suspended']);

        foreach (['tables.index', 'dashboard.index', 'cash-register.manage', 'products.manage'] as $route) {
            $this->actingAs($this->ownerA)
                ->get(route($route))
                ->assertRedirect(route('account.locked'));
        }

        $this->actingAs($this->ownerA)
            ->get(route('account.locked'))
            ->assertOk()
            ->assertSee('Accès suspendu');
    }

    public function test_un_maquis_dont_l_essai_est_termine_est_bloque(): void
    {
        $this->maquisA->update(['subscription_status' => 'trial', 'trial_ends_at' => now()->subDay()]);

        $this->actingAs($this->ownerA)
            ->get(route('tables.index'))
            ->assertRedirect(route('account.locked'));
    }

    public function test_un_maquis_en_essai_valide_fonctionne(): void
    {
        $this->maquisA->update(['subscription_status' => 'trial', 'trial_ends_at' => now()->addDays(5)]);

        $this->actingAs($this->ownerA)
            ->get(route('tables.index'))
            ->assertOk();
    }

    public function test_le_super_admin_garde_l_acces_a_un_maquis_verrouille(): void
    {
        $this->maquisB->update(['subscription_status' => 'suspended']);
        $this->enterAs($this->admin, $this->maquisB);

        $this->get(route('tables.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Comptes désactivés
    // ---------------------------------------------------------------

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $serveur = User::factory()->server()->inactive()->create(['restaurant_id' => $this->maquisA->id]);

        Livewire::test(Login::class)
            ->set('phone', $serveur->phone)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertHasErrors('phone');

        $this->assertGuest();
    }

    public function test_un_compte_desactive_pendant_sa_session_est_deconnecte(): void
    {
        $serveur = User::factory()->server()->create(['restaurant_id' => $this->maquisA->id]);
        $this->actingAs($serveur);

        $serveur->update(['status' => 'inactive']);

        $this->get(route('tables.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
