<?php

namespace Tests\Feature;

use App\Jobs\AlertNewDemoRequest;
use App\Livewire\DemoRequestForm;
use App\Models\DemoRequest;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\WhatsAppCloud;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Le Super Admin est prévenu à chaque nouvelle demande de démo
 * (e-mail et/ou WhatsApp), pour rappeler le prospect vite.
 */
class DemoAlertTest extends TestCase
{
    use RefreshDatabase;

    private function demander(string $phone = '07 88 99 00 11', string $website = '')
    {
        return Livewire::test(DemoRequestForm::class)
            ->set('contactName', 'Koffi Yao')
            ->set('restaurantName', 'Maquis Chez Koffi')
            ->set('phone', $phone)
            ->set('commune', 'Yopougon')
            ->set('website', $website)
            ->call('submit');
    }

    public function test_une_nouvelle_demande_declenche_l_alerte(): void
    {
        Queue::fake();

        $this->demander()->assertHasNoErrors();

        Queue::assertPushed(AlertNewDemoRequest::class, fn ($job) => $job->demoRequestId === DemoRequest::sole()->id);
    }

    public function test_pas_de_deuxieme_alerte_pour_le_meme_numero(): void
    {
        Queue::fake();

        $this->demander();
        $this->demander();

        Queue::assertPushed(AlertNewDemoRequest::class, 1);
    }

    public function test_pas_d_alerte_pour_un_robot(): void
    {
        Queue::fake();

        $this->demander('0788990011', 'http://spam.example');

        Queue::assertNothingPushed();
    }

    public function test_l_alerte_part_par_e_mail(): void
    {
        config(['maquis.alerts.email' => 'patron-plateforme@exemple.ci', 'mail.default' => 'array']);
        $demo = DemoRequest::factory()->create(['contact_name' => 'Koffi Yao', 'restaurant_name' => 'Maquis Chez Koffi', 'phone' => '0788990011']);

        (new AlertNewDemoRequest($demo->id))->handle(app(WhatsAppCloud::class));

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages->first()->getOriginalMessage();
        $this->assertSame('Nouvelle demande de démo : Maquis Chez Koffi', $email->getSubject());
        $this->assertStringContainsString('Téléphone / WhatsApp : 0788990011', $email->getTextBody());
        $this->assertStringContainsString('https://wa.me/2250788990011', $email->getTextBody());
    }

    public function test_l_alerte_part_par_whatsapp(): void
    {
        config([
            'maquis.alerts.whatsapp' => '0700000000',
            'services.whatsapp.token' => 'jeton',
            'services.whatsapp.phone_number_id' => '123',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);
        $demo = DemoRequest::factory()->create(['contact_name' => 'Koffi Yao', 'restaurant_name' => 'Maquis Chez Koffi', 'phone' => '0788990011']);

        (new AlertNewDemoRequest($demo->id))->handle(app(WhatsAppCloud::class));

        Http::assertSent(function (Request $r) {
            $params = collect($r->data()['template']['components'][0]['parameters'])->pluck('text');

            return $r->data()['to'] === '2250700000000'
                && $r->data()['template']['name'] === 'nouvelle_demande'
                && $params[0] === 'Koffi Yao'
                && $params[1] === 'Maquis Chez Koffi'
                && $params[2] === '0788990011';
        });
    }

    public function test_sans_configuration_rien_ne_part_et_rien_ne_plante(): void
    {
        Http::fake();
        config(['maquis.alerts.email' => null, 'maquis.alerts.whatsapp' => null]);
        $demo = DemoRequest::factory()->create();

        (new AlertNewDemoRequest($demo->id))->handle(app(WhatsAppCloud::class));

        Http::assertNothingSent();
        $this->assertTrue(true);
    }

    public function test_le_super_admin_voit_le_compteur_des_nouvelles_demandes(): void
    {
        DemoRequest::factory()->count(2)->create(['status' => 'new']);
        DemoRequest::factory()->create(['status' => 'contacted']);
        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.restaurants'))
            ->assertSee('2 nouvelles demandes de démo');
    }
}
