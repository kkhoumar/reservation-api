<?php

namespace Tests\Feature;

use App\Livewire\Admin\CreateRestaurant;
use App\Livewire\ChangePassword;
use App\Livewire\Login;
use App\Livewire\UsersManage;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Installation d'un client par le Super Admin, numéros uniformisés,
 * et changement obligatoire du mot de passe provisoire.
 */
class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['restaurant_id' => null]);
        $this->admin->forceFill(['is_platform_admin' => true])->save();
    }

    private function creerMaquis(array $overrides = [])
    {
        $component = Livewire::actingAs($this->admin)->test(CreateRestaurant::class);

        foreach (array_merge([
            'name' => 'Maquis Chez Tantie',
            'restaurantPhone' => '27 22 33 44 55',
            'commune' => 'Yopougon',
            'trialDays' => 14,
            'ownerFirstName' => 'Awa',
            'ownerLastName' => 'Koné',
            'ownerPhone' => '+225 07 11 22 33 44',
        ], $overrides) as $field => $value) {
            $component->set($field, $value);
        }

        return $component->call('save');
    }

    // ---------------------------------------------------------------
    // Numéros de téléphone
    // ---------------------------------------------------------------

    public function test_les_numeros_sont_uniformises(): void
    {
        foreach (['07 11 22 33 44', '0711223344', '+225 07 11 22 33 44', '00225 07.11.22.33.44', '07-11-22-33-44'] as $saisie) {
            $this->assertSame('0711223344', User::normalizePhone($saisie), $saisie);
        }

        // Mobiles et fixes acceptés, le reste refusé
        foreach (['0711223344', '0544556677', '0102030405', '2722334455', '2521222324'] as $valide) {
            $this->assertMatchesRegularExpression(User::PHONE_REGEX, $valide);
        }
        foreach (['12345', '711223344', '9911223344', '07112233445'] as $invalide) {
            $this->assertDoesNotMatchRegularExpression(User::PHONE_REGEX, $invalide);
        }
    }

    public function test_on_se_connecte_quelle_que_soit_l_ecriture_du_numero(): void
    {
        $user = User::factory()->create(['phone' => '0711223344']);

        Livewire::test(Login::class)
            ->set('phone', '+225 07 11 22 33 44')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    // ---------------------------------------------------------------
    // Création d'un maquis par le Super Admin
    // ---------------------------------------------------------------

    public function test_le_super_admin_cree_un_maquis_et_son_proprietaire(): void
    {
        $component = $this->creerMaquis()->assertHasNoErrors();

        $restaurant = Restaurant::sole();
        $this->assertSame('Maquis Chez Tantie', $restaurant->name);
        $this->assertSame('trial', $restaurant->subscription_status);
        $this->assertTrue($restaurant->trial_ends_at->isSameDay(now()->addDays(14)));

        $owner = User::where('phone', '0711223344')->sole();
        $this->assertSame('owner', $owner->role);
        $this->assertSame($restaurant->id, $owner->restaurant_id);
        $this->assertTrue($owner->mustChangePassword());
        $this->assertFalse($owner->isPlatformAdmin());

        // Le mot de passe provisoire affiché est bien celui du compte
        $password = $component->get('created')['password'];
        $this->assertTrue(Hash::check($password, $owner->password));
        $component->assertSee($password);
    }

    public function test_zero_jour_d_essai_donne_un_abonnement_actif(): void
    {
        $this->creerMaquis(['trialDays' => 0])->assertHasNoErrors();

        $this->assertSame('active', Restaurant::sole()->subscription_status);
        $this->assertNull(Restaurant::sole()->trial_ends_at);
    }

    public function test_un_numero_deja_utilise_est_refuse_et_rien_n_est_cree(): void
    {
        User::factory()->create(['phone' => '0711223344']);
        $avant = Restaurant::count();

        $this->creerMaquis(['ownerPhone' => '07 11 22 33 44'])->assertHasErrors('ownerPhone');

        $this->assertSame($avant, Restaurant::count());
    }

    public function test_un_numero_invalide_est_refuse(): void
    {
        $this->creerMaquis(['ownerPhone' => '12345'])->assertHasErrors('ownerPhone');

        $this->assertSame(0, Restaurant::count());
    }

    public function test_un_patron_n_a_pas_acces_a_la_creation_de_maquis(): void
    {
        $restaurant = Restaurant::factory()->create();
        $owner = User::factory()->owner()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($owner)
            ->get(route('admin.restaurants.create'))
            ->assertRedirect(route('tables.index'));
    }

    public function test_le_mot_de_passe_provisoire_est_facile_a_dicter(): void
    {
        $password = \App\Actions\CreateRestaurantWithOwner::temporaryPassword();

        $this->assertMatchesRegularExpression('/^[a-zA-Z2-9]{4}-[a-zA-Z2-9]{4}$/', $password);
        $this->assertDoesNotMatchRegularExpression('/[01lIoO]/', $password);
    }

    // ---------------------------------------------------------------
    // Première connexion
    // ---------------------------------------------------------------

    private function nouveauProprietaire(): User
    {
        $restaurant = Restaurant::factory()->create();
        $owner = User::factory()->owner()->create(['restaurant_id' => $restaurant->id, 'phone' => '0711223344']);
        $owner->forceFill(['must_change_password' => true])->save();

        return $owner;
    }

    public function test_a_la_premiere_connexion_il_faut_changer_le_mot_de_passe(): void
    {
        $this->nouveauProprietaire();

        Livewire::test(Login::class)
            ->set('phone', '0711223344')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('password.change'));
    }

    public function test_tant_que_le_mot_de_passe_n_est_pas_change_les_ecrans_sont_bloques(): void
    {
        $owner = $this->nouveauProprietaire();

        foreach (['tables.index', 'dashboard.index', 'products.manage'] as $route) {
            $this->actingAs($owner)->get(route($route))->assertRedirect(route('password.change'));
        }
    }

    public function test_changer_le_mot_de_passe_debloque_l_acces(): void
    {
        $owner = $this->nouveauProprietaire();

        Livewire::actingAs($owner)->test(ChangePassword::class)
            ->set('currentPassword', 'password')
            ->set('password', 'MonMaquis2026')
            ->set('passwordConfirmation', 'MonMaquis2026')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard.index'));

        $owner->refresh();
        $this->assertFalse($owner->mustChangePassword());
        $this->assertTrue(Hash::check('MonMaquis2026', $owner->password));

        $this->actingAs($owner)->get(route('tables.index'))->assertOk();
    }

    public function test_le_nouveau_mot_de_passe_doit_etre_valide(): void
    {
        $owner = $this->nouveauProprietaire();

        // Mauvais mot de passe actuel
        Livewire::actingAs($owner)->test(ChangePassword::class)
            ->set('currentPassword', 'faux')
            ->set('password', 'MonMaquis2026')
            ->set('passwordConfirmation', 'MonMaquis2026')
            ->call('save')
            ->assertHasErrors('currentPassword');

        // Trop court, différent de la confirmation, identique à l'ancien
        foreach ([['court', 'court'], ['MonMaquis2026', 'Autre2026xx'], ['password', 'password']] as [$new, $confirm]) {
            Livewire::actingAs($owner)->test(ChangePassword::class)
                ->set('currentPassword', 'password')
                ->set('password', $new)
                ->set('passwordConfirmation', $confirm)
                ->call('save')
                ->assertHasErrors('password');
        }

        $this->assertTrue($owner->fresh()->mustChangePassword());
    }

    public function test_un_employe_cree_par_la_gerante_doit_aussi_changer_son_mot_de_passe(): void
    {
        $restaurant = Restaurant::factory()->create();
        $manager = User::factory()->manager()->create(['restaurant_id' => $restaurant->id]);

        Livewire::actingAs($manager)->test(UsersManage::class)
            ->call('openCreateForm')
            ->set('firstName', 'Yao')
            ->set('lastName', 'Kouadio')
            ->set('phone', '05 44 55 66 77')
            ->set('password', 'provisoire1')
            ->set('role', 'server')
            ->call('save')
            ->assertHasNoErrors();

        $serveur = User::where('phone', '0544556677')->sole();
        $this->assertTrue($serveur->mustChangePassword());
    }
}
