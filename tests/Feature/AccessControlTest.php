<?php

namespace Tests\Feature;

use App\Livewire\ExpensesList;
use App\Livewire\Login;
use App\Livewire\UsersManage;
use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Connexion protégée contre la force brute, et droits de chaque rôle.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $owner;
    private User $manager;
    private User $server;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login:+2250500000000|127.0.0.1');

        $this->maquis = Restaurant::factory()->create();
        $this->owner = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'phone' => '+225 05 00 00 00 00']);
        $this->manager = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id]);
        $this->server = User::factory()->server()->create(['restaurant_id' => $this->maquis->id]);
    }

    // ---------------------------------------------------------------
    // Connexion
    // ---------------------------------------------------------------

    private function tryLogin(string $password)
    {
        return Livewire::test(Login::class)
            ->set('phone', '+225 05 00 00 00 00')
            ->set('password', $password)
            ->call('authenticate');
    }

    public function test_la_connexion_fonctionne_avec_le_bon_mot_de_passe(): void
    {
        $this->tryLogin('password')->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_apres_5_echecs_la_connexion_est_bloquee_meme_avec_le_bon_mot_de_passe(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->tryLogin('mauvais');
        }

        $this->tryLogin('password')
            ->assertHasErrors('phone')
            ->assertSee('Trop de tentatives');

        $this->assertGuest();
    }

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $this->owner->update(['status' => 'inactive']);

        $this->tryLogin('password')->assertHasErrors('phone');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------
    // Pages réservées
    // ---------------------------------------------------------------

    public function test_un_serveur_n_accede_pas_aux_pages_de_gestion(): void
    {
        $this->actingAs($this->server);

        foreach (['dashboard.index', 'products.manage', 'users.manage', 'cash-register.history'] as $route) {
            $this->get(route($route))->assertRedirect(route('tables.index'));
        }
    }

    // ---------------------------------------------------------------
    // Gestion du personnel
    // ---------------------------------------------------------------

    public function test_une_gerante_ne_peut_pas_modifier_le_proprietaire(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(UsersManage::class)
            ->call('editUser', $this->owner->id)
            ->assertForbidden();

        Livewire::test(UsersManage::class)
            ->call('toggleStatus', $this->owner->id)
            ->assertForbidden();

        $this->assertSame('active', $this->owner->fresh()->status);
    }

    public function test_une_gerante_ne_peut_pas_changer_le_mot_de_passe_du_proprietaire(): void
    {
        $this->actingAs($this->manager);
        $ancienHash = $this->owner->password;

        // Le formulaire ne s'ouvre pas sur le propriétaire…
        $component = Livewire::test(UsersManage::class)
            ->call('editUser', $this->owner->id)
            ->assertForbidden();

        // …et l'identifiant ciblé ne peut pas être forcé depuis le navigateur
        try {
            Livewire::test(UsersManage::class)->set('editingId', $this->owner->id);
            $this->fail('editingId devrait être verrouillé');
        } catch (\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($ancienHash, $this->owner->fresh()->password);
        $this->assertTrue(Hash::check('password', $this->owner->fresh()->password));
    }

    public function test_une_gerante_ne_peut_pas_creer_un_gerant(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(UsersManage::class)
            ->call('openCreateForm')
            ->set('firstName', 'Awa')
            ->set('lastName', 'Koné')
            ->set('phone', '+225 07 11 22 33 44')
            ->set('password', 'secret123')
            ->set('role', 'manager')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertDatabaseMissing('users', ['phone' => '+225 07 11 22 33 44']);
    }

    public function test_une_gerante_ne_peut_pas_creer_un_proprietaire(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(UsersManage::class)
            ->call('openCreateForm')
            ->set('firstName', 'Awa')
            ->set('lastName', 'Koné')
            ->set('phone', '+225 07 11 22 33 44')
            ->set('password', 'secret123')
            ->set('role', 'owner')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertDatabaseMissing('users', ['phone' => '+225 07 11 22 33 44']);
    }

    public function test_une_gerante_peut_gerer_les_serveurs(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(UsersManage::class)
            ->call('openCreateForm')
            ->set('firstName', 'Yao')
            ->set('lastName', 'Kouadio')
            ->set('phone', '+225 07 55 66 77 88')
            ->set('password', 'secret123')
            ->set('role', 'server')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['phone' => '+225 07 55 66 77 88', 'role' => 'server', 'restaurant_id' => $this->maquis->id]);

        Livewire::test(UsersManage::class)->call('toggleStatus', $this->server->id);
        $this->assertSame('inactive', $this->server->fresh()->status);
    }

    public function test_une_gerante_ne_peut_pas_promouvoir_un_serveur_en_gerant(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(UsersManage::class)
            ->call('editUser', $this->server->id)
            ->set('role', 'manager')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame('server', $this->server->fresh()->role);
    }

    public function test_le_proprietaire_gere_les_gerants(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(UsersManage::class)->call('toggleStatus', $this->manager->id);

        $this->assertSame('inactive', $this->manager->fresh()->status);
    }

    public function test_le_proprietaire_modifie_son_profil_sans_perdre_son_role(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(UsersManage::class)
            ->call('editUser', $this->owner->id)
            ->set('firstName', 'Koffi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Koffi', $this->owner->fresh()->first_name);
        $this->assertSame('owner', $this->owner->fresh()->role);
    }

    public function test_personne_ne_peut_se_desactiver_soi_meme(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(UsersManage::class)->call('toggleStatus', $this->owner->id);

        $this->assertSame('active', $this->owner->fresh()->status);
    }

    // ---------------------------------------------------------------
    // Dépenses
    // ---------------------------------------------------------------

    public function test_une_categorie_de_depense_inconnue_est_refusee(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(ExpensesList::class)
            ->set('category', 'vacances')
            ->set('amount', 5000)
            ->call('addExpense')
            ->assertHasErrors('category');

        $this->assertSame(0, Expense::count());
    }

    public function test_la_liste_des_categories_ne_peut_pas_etre_modifiee_depuis_le_navigateur(): void
    {
        $this->actingAs($this->manager);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(ExpensesList::class)->set('categories', ['vacances' => 'Vacances']);
    }
}
