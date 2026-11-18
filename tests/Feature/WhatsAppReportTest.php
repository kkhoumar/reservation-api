<?php

namespace Tests\Feature;

use App\Jobs\SendRegisterReport;
use App\Livewire\CashRegisterClosed;
use App\Livewire\CashRegisterManage;
use App\Livewire\RestaurantSettings;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\WhatsAppCloud;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rapport du soir envoyé automatiquement au patron par l'API WhatsApp
 * Business, juste après la clôture de caisse. WhatsApp est simulé.
 */
class WhatsAppReportTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;
    private CashRegister $caisse;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.token' => 'jeton-de-test',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.report_template' => 'rapport_caisse',
        ]);

        $this->maquis = Restaurant::factory()->create(['name' => 'Maquis Chez Koffi']);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'phone' => '0711111111']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->jean->id,
            'opening_amount' => 10000,
            'opened_at' => now()->subHours(4),
        ]);

        $order = Order::factory()->awaitingPayment(5000)->create(['restaurant_id' => $this->maquis->id]);
        Payment::recordCashPayment($order, $this->jean, 5000);
    }

    private function fakeWhatsAppOk(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]], 200)]);
    }

    private function cloturer(int $compte = 15000): void
    {
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->set('directEntry', true)
            ->set('countedAmount', $compte)
            ->set('closingNote', $compte !== 15000 ? "Billet\nabîmé" : '')
            ->call('confirmClose')
            ->assertRedirect(route('cash-register.closed', $this->caisse->id));
    }

    public function test_la_cloture_envoie_le_rapport_au_patron(): void
    {
        $this->fakeWhatsAppOk();

        $this->cloturer();

        $caisse = $this->caisse->fresh();
        $this->assertSame('sent', $caisse->report_status);
        $this->assertSame('0711111111', $caisse->report_phone);
        $this->assertNotNull($caisse->report_sent_at);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            $params = collect($body['template']['components'][0]['parameters'])->pluck('text');

            return str_contains($request->url(), '/123456/messages')
                && $request->hasHeader('Authorization', 'Bearer jeton-de-test')
                && $body['to'] === '2250711111111'
                && $body['template']['name'] === 'rapport_caisse'
                && $params->count() === 10
                && $params[0] === 'Maquis Chez Koffi'
                && $params[2] === '5 000'
                && $params[3] === '1'
                && $params[8] === '0 FCFA ✅';
        });
    }

    public function test_les_variables_tiennent_sur_une_ligne_comme_l_exige_meta(): void
    {
        $this->fakeWhatsAppOk();

        $this->cloturer(14000);

        Http::assertSent(function (Request $request) {
            $gap = $request->data()['template']['components'][0]['parameters'][8]['text'];

            return $gap === '-1 000 FCFA ⚠️ (Billet abîmé)';
        });
    }

    public function test_le_rapport_part_au_numero_choisi_par_le_patron(): void
    {
        $this->fakeWhatsAppOk();
        $this->maquis->update(['report_phone' => '0505050505']);

        $this->cloturer();

        Http::assertSent(fn (Request $r) => $r->data()['to'] === '2250505050505');
    }

    public function test_pas_d_envoi_si_le_patron_l_a_desactive(): void
    {
        Http::fake();
        $this->maquis->update(['whatsapp_report_enabled' => false]);

        $this->cloturer();

        Http::assertNothingSent();
        $this->assertNull($this->caisse->fresh()->report_status);
    }

    public function test_pas_d_envoi_tant_que_whatsapp_n_est_pas_configure(): void
    {
        Http::fake();
        config(['services.whatsapp.token' => null]);

        $this->cloturer();

        Http::assertNothingSent();
        $this->assertNull($this->caisse->fresh()->report_status);
    }

    public function test_un_refus_de_whatsapp_est_note_et_l_ecran_propose_de_reessayer(): void
    {
        // 1er appel : refusé par WhatsApp ; 2e appel (« Réessayer ») : accepté
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Template name does not exist']], 400)
            ->push(['messages' => [['id' => 'wamid.TEST']]], 200)]);
        $this->caisse->close(15000, $this->jean->id);
        $job = new SendRegisterReport($this->caisse->id);

        try {
            $job->handle(app(WhatsAppCloud::class));
            $this->fail('Le refus de WhatsApp aurait dû lever une erreur.');
        } catch (RuntimeException $e) {
            $job->failed($e);
        }

        $caisse = $this->caisse->fresh();
        $this->assertSame('failed', $caisse->report_status);
        $this->assertStringContainsString('Template name does not exist', $caisse->report_error);

        $this->actingAs($this->jean);

        Livewire::test(CashRegisterClosed::class, ['register' => $this->caisse->id])
            ->assertSee("L'envoi automatique a échoué", false)
            ->call('resend')
            ->assertSee('Envoyé automatiquement');

        $this->assertSame('sent', $this->caisse->fresh()->report_status);
    }

    public function test_le_rapport_n_est_jamais_envoye_deux_fois(): void
    {
        $this->fakeWhatsAppOk();
        $this->cloturer();

        (new SendRegisterReport($this->caisse->id))->handle(app(WhatsAppCloud::class));

        Http::assertSentCount(1);
    }

    public function test_le_patron_regle_l_envoi_dans_les_parametres(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(RestaurantSettings::class)
            ->assertSet('whatsappReportEnabled', true)
            ->set('reportPhone', '+225 05 05 05 05 05')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('0505050505', $this->maquis->fresh()->report_phone);

        Livewire::test(RestaurantSettings::class)
            ->set('reportPhone', '123')
            ->call('save')
            ->assertHasErrors('reportPhone');
    }
}
