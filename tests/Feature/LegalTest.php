<?php

namespace Tests\Feature;

use App\Livewire\AcceptTerms;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Conditions d'utilisation et politique de confidentialité : pages publiques,
 * acceptation obligatoire par le patron (et à chaque nouvelle version).
 */
class LegalTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create();
    }

    public function test_les_deux_pages_sont_publiques(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee("Conditions d'utilisation", false)->assertSee('Retard de paiement');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Politique de confidentialité')->assertSee('2013-450');
    }

    public function test_les_informations_de_l_editeur_viennent_de_la_configuration(): void
    {
        $this->get(route('legal.terms'))->assertSee('[à compléter]');

        config(['maquis.legal.company' => 'Maquis Express SARL', 'maquis.legal.email' => 'contact@exemple.ci']);

        $this->get(route('legal.terms'))->assertSee('Maquis Express SARL')->assertSee('contact@exemple.ci');
        $this->get(route('legal.privacy'))->assertSee('contact@exemple.ci');
    }

    public function test_les_conditions_reprennent_les_regles_de_l_application(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee(\App\Models\Restaurant::GRACE_DAYS . ' jours de grâce')
            ->assertSee('le matin à ' . \App\Models\Restaurant::LOCK_HOUR . ' h');
    }

    public function test_les_liens_sont_sur_la_connexion_et_la_demande_de_demo(): void
    {
        $this->get(route('login'))->assertSee(route('legal.terms'), false)->assertSee(route('legal.privacy'), false);
        $this->get(route('demo.request'))->assertSee(route('legal.privacy'), false);
    }

    public function test_un_nouveau_patron_doit_accepter_avant_d_utiliser_l_application(): void
    {
        $patron = User::factory()->owner()->withoutAcceptedTerms()->create(['restaurant_id' => $this->maquis->id]);

        $this->actingAs($patron)
            ->get(route('tables.index'))
            ->assertRedirect(route('terms.accept'));

        $this->get(route('terms.accept'))->assertOk()->assertSee('Bienvenue sur Maquis Express');
    }

    public function test_il_faut_cocher_la_case(): void
    {
        $patron = User::factory()->owner()->withoutAcceptedTerms()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($patron);

        Livewire::test(AcceptTerms::class)
            ->call('accept')
            ->assertHasErrors('accepted');

        $this->assertNull($patron->fresh()->terms_accepted_at);

        Livewire::test(AcceptTerms::class)
            ->set('accepted', true)
            ->call('accept')
            ->assertRedirect(route('dashboard.index'));

        $patron->refresh();
        $this->assertNotNull($patron->terms_accepted_at);
        $this->assertSame(config('maquis.terms_version'), $patron->terms_version);
        $this->actingAs($patron)->get(route('tables.index'))->assertOk();
    }

    public function test_une_nouvelle_version_doit_etre_acceptee_de_nouveau(): void
    {
        $patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        config(['maquis.terms_version' => '2027-01-01']);

        $this->actingAs($patron)
            ->get(route('tables.index'))
            ->assertRedirect(route('terms.accept'));

        $this->get(route('terms.accept'))->assertSee('Nos conditions ont changé');
    }

    public function test_le_personnel_et_le_super_admin_n_ont_rien_a_accepter(): void
    {
        $serveuse = User::factory()->server()->withoutAcceptedTerms()->create(['restaurant_id' => $this->maquis->id]);
        $caissier = User::factory()->cashier()->withoutAcceptedTerms()->create(['restaurant_id' => $this->maquis->id]);

        $this->assertFalse($serveuse->mustAcceptTerms());
        $this->assertFalse($caissier->mustAcceptTerms());
        $this->actingAs($caissier)->get(route('cash-register.manage'))->assertOk();

        $admin = User::factory()->withoutAcceptedTerms()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->assertFalse($admin->mustAcceptTerms());
    }

    public function test_la_page_d_acceptation_exige_d_etre_connecte(): void
    {
        $this->get(route('terms.accept'))->assertRedirect(route('login'));
    }
}
