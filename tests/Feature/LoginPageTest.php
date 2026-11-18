<?php

namespace Tests\Feature;

use App\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Page de connexion : affichage, « Rester connecté », aide mot de passe oublié.
 */
class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_de_connexion_s_affiche(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bon retour')
            ->assertSee('Mot de passe oublié ?')
            ->assertSee('Rester connecté sur cet appareil')
            ->assertSee(route('demo.request'), false);
    }

    public function test_rester_connecte_garde_la_session_sur_l_appareil(): void
    {
        $user = User::factory()->owner()->create(['phone' => '0711111111']);

        Livewire::test(Login::class)
            ->set('phone', '07 11 11 11 11')
            ->set('password', 'password')
            ->set('remember', true)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_sans_rester_connecte_pas_de_jeton_memorise(): void
    {
        $user = User::factory()->owner()->create(['phone' => '0711111111', 'remember_token' => null]);

        Livewire::test(Login::class)
            ->set('phone', '0711111111')
            ->set('password', 'password')
            ->call('authenticate');

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_l_aide_propose_whatsapp_si_configure(): void
    {
        config(['maquis.billing.whatsapp' => '0700000000']);

        $this->get(route('login'))->assertSee('https://wa.me/2250700000000', false);
    }
}
