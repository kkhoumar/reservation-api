<?php

namespace Tests\Feature;

use App\Livewire\Admin\RestaurantsList;
use App\Livewire\Admin\RestaurantSubscription;
use App\Models\DemoRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin : nom du patron sur la liste, mot de passe oublié (patron compris),
 * demande de démo d'un numéro qui a déjà un compte.
 */
class AdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Restaurant $kineo;
    private User $patron;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['restaurant_id' => null, 'role' => 'owner']);
        $this->admin->forceFill(['is_platform_admin' => true])->save();
        $this->kineo = Restaurant::factory()->create(['name' => 'Kinéo']);
        $this->patron = User::factory()->owner()->create([
            'restaurant_id' => $this->kineo->id, 'first_name' => 'Kouassi', 'last_name' => 'Yao',
            'phone' => '0711223344', 'password' => 'Ancien-Mot-2026',
        ]);
        $this->actingAs($this->admin);
    }

    public function test_la_liste_montre_le_patron_et_cherche_par_patron_ou_numero(): void
    {
        Restaurant::factory()->create(['name' => 'Chez Awa']);

        Livewire::test(RestaurantsList::class)
            ->assertSee('Kouassi Yao')
            ->assertSee('07 11 22 33 44')
            ->assertSee('Pas de patron actif') // Chez Awa n'a pas de patron
            ->set('search', 'kouassi')->assertSee('Kinéo')->assertDontSee('Chez Awa')
            ->set('search', '2233')->assertSee('Kinéo')->assertDontSee('Chez Awa')
            ->set('search', 'awa')->assertSee('Chez Awa')->assertDontSee('Kouassi Yao')
            ->set('search', 'zzz')->assertSee('Aucun maquis ne correspond');
    }

    public function test_le_super_admin_donne_un_nouveau_mot_de_passe_au_patron(): void
    {
        DB::table('sessions')->insert(['id' => 'tel-du-patron', 'user_id' => $this->patron->id, 'payload' => '', 'last_activity' => time()]);

        $page = Livewire::test(RestaurantSubscription::class, ['restaurant' => $this->kineo])
            ->assertSee('Comptes du maquis')
            ->call('resetUserPassword', $this->patron->id)
            ->assertSee('Envoyer sur WhatsApp');

        $password = $page->get('newPassword')['password'];
        $patron = $this->patron->fresh();
        $this->assertTrue(Hash::check($password, $patron->password));
        $this->assertFalse(Hash::check('Ancien-Mot-2026', $patron->password));
        $this->assertTrue((bool) $patron->must_change_password);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $patron->id)->count(), 'déconnecté partout');
        $this->assertStringContainsString('wa.me/2250711223344', $page->html());

        // Le patron le voit dans son Journal
        $log = DB::table('activity_logs')->where('restaurant_id', $this->kineo->id)->where('action', 'password_reset')->sole();
        $this->assertStringContainsString('Le support Maquis Express', $log->description);

        // Montré une seule fois
        $page->call('hideNewPassword')->assertDontSee($password);
    }

    public function test_impossible_de_toucher_un_compte_d_un_autre_maquis(): void
    {
        $autre = Restaurant::factory()->create();
        $intrus = User::factory()->owner()->create(['restaurant_id' => $autre->id, 'password' => 'Pas-Touche-2026']);

        try {
            Livewire::test(RestaurantSubscription::class, ['restaurant' => $this->kineo])->call('resetUserPassword', $intrus->id);
        } catch (ModelNotFoundException) {
        }

        $this->assertTrue(Hash::check('Pas-Touche-2026', $intrus->fresh()->password));
    }

    public function test_seul_le_super_admin_ouvre_cette_page(): void
    {
        $this->actingAs($this->patron)->get(route('admin.restaurants.subscription', $this->kineo->id))->assertRedirect();
    }

    public function test_demande_de_demo_d_un_numero_deja_client_ou_employe(): void
    {
        $serveuse = User::factory()->create(['restaurant_id' => $this->kineo->id, 'role' => 'server', 'phone' => '0522334455']);
        DemoRequest::create(['contact_name' => 'Kouassi', 'restaurant_name' => 'Kinéo 2', 'phone' => '0711223344']);
        DemoRequest::create(['contact_name' => 'Awa', 'restaurant_name' => 'Chez Awa', 'phone' => $serveuse->phone]);
        DemoRequest::create(['contact_name' => 'Nouveau', 'restaurant_name' => 'Le Nouveau', 'phone' => '0199887766']);

        Livewire::test(RestaurantsList::class)
            ->assertSee('Déjà client : Kinéo')
            ->assertSee('Nouveau mot de passe')
            ->assertSee('Serveur chez Kinéo')
            ->assertSee('il faudra un autre numéro', false);

        $this->kineo->forceFill(['subscription_status' => 'suspended'])->save();
        Livewire::test(RestaurantsList::class)->assertSee('Ancien client : Kinéo');
    }

    public function test_le_visiteur_n_apprend_jamais_que_le_numero_existe(): void
    {
        auth()->logout();

        Livewire::test(\App\Livewire\DemoRequestForm::class)
            ->set('contactName', 'Kouassi Yao')
            ->set('restaurantName', 'Kinéo 2')
            ->set('phone', '0711223344')
            ->call('submit')
            ->assertSee("Merci, c'est noté", false)
            ->assertSee('Déjà client ?')
            ->assertDontSee('Kinéo');
    }
}
