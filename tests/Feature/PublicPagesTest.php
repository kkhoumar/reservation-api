<?php

namespace Tests\Feature;

use App\Livewire\DemoRequestForm;
use App\Models\DemoRequest;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pages publiques : présentation de Maquis Express et demande de démo.
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_d_accueil_presente_les_vraies_fonctionnalites(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Bon de comptoir')
            ->assertSee('Espèces et Mobile Money')
            ->assertSee('Crédits clients')
            ->assertSee('Stock surveillé')
            ->assertSee('Le rapport du soir sur WhatsApp')
            ->assertSee('14 jours d')
            ->assertSee("Installer l'application mobile", false)
            ->assertSee(route('demo.request'), false);
    }

    public function test_les_tarifs_viennent_des_formules_reelles(): void
    {
        Plan::where('code', 'pro')->update(['monthly_price' => 12500]);
        Plan::where('code', 'premium')->update(['is_active' => false]);

        $this->get(route('landing'))
            ->assertSee('Basic')
            ->assertSee('12 500')
            ->assertSee("Jusqu'à 3 utilisateurs")
            ->assertDontSee('Premium');
    }

    public function test_plus_de_promesses_fausses(): void
    {
        $this->get(route('landing'))
            ->assertDontSee('Créez votre établissement')
            ->assertDontSee('illimités')
            ->assertDontSee('LE PLUS CHOISI');
    }

    public function test_le_formulaire_de_demo_ajoute_le_type_et_la_taille_de_l_equipe(): void
    {
        Livewire::test(DemoRequestForm::class)
            ->assertSee('Demander une démo')
            ->set('contactFirstName', 'Koffi')->set('contactLastName', 'Yao')
            ->set('restaurantName', 'Maquis Chez Koffi')
            ->set('phone', '07 88 99 00 11')
            ->call('$set', 'establishmentType', 'Bar')
            ->set('teamSize', '2-5')
            ->set('message', '12 tables')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee("Merci, c'est noté", false);

        $this->assertSame('Type : Bar · Équipe : 2 à 5 personnes · 12 tables', DemoRequest::sole()->message);
    }

    public function test_type_et_taille_inconnus_refuses(): void
    {
        Livewire::test(DemoRequestForm::class)
            ->set('contactFirstName', 'Koffi')->set('contactLastName', 'Yao')
            ->set('restaurantName', 'Maquis Chez Koffi')
            ->set('phone', '0788990011')
            ->set('establishmentType', 'Casino')
            ->set('teamSize', '500')
            ->call('submit')
            ->assertHasErrors(['establishmentType', 'teamSize']);

        $this->assertSame(0, DemoRequest::count());
    }

    public function test_le_contact_whatsapp_apparait_s_il_est_configure(): void
    {
        $this->get(route('demo.request'))->assertDontSee('Écrire sur WhatsApp');

        config(['maquis.billing.whatsapp' => '0700000000']);

        $this->get(route('demo.request'))->assertSee('https://wa.me/2250700000000', false);
        $this->get(route('landing'))->assertSee('Nous écrire sur WhatsApp');
    }
}
