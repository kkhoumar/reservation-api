<?php

namespace Tests\Feature;

use App\Jobs\SendRegisterReport;
use App\Livewire\Admin\PlansManage;
use App\Livewire\CashRegisterManage;
use App\Models\CashRegister;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\WhatsAppCloud;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Le rapport du soir automatique sur WhatsApp : inclus en Pro et Premium,
 * pendant l'essai, mais pas en Basic (qui garde l'envoi d'un clic).
 */
class PlanFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.token' => 'jeton', 'services.whatsapp.phone_number_id' => '123']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'phone' => '0711111111']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
    }

    private function formule(string $code): void
    {
        $this->maquis->forceFill(['plan_id' => Plan::where('code', $code)->value('id')])->save();
    }

    private function cloturer(): CashRegister
    {
        $caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id, 'opened_by' => $this->jean->id,
            'opening_amount' => 0, 'opened_at' => now()->subHour(),
        ]);
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterManage::class)
            ->set('directEntry', true)
            ->set('countedAmount', 0)
            ->call('confirmClose');

        return $caisse->fresh();
    }

    public function test_les_formules_de_depart(): void
    {
        $this->assertFalse(Plan::where('code', 'basic')->value('includes_auto_report'));
        $this->assertTrue(Plan::where('code', 'pro')->value('includes_auto_report'));
        $this->assertTrue(Plan::where('code', 'premium')->value('includes_auto_report'));
    }

    public function test_en_basic_pas_d_envoi_automatique(): void
    {
        $this->formule('basic');

        $caisse = $this->cloturer();

        Http::assertNothingSent();
        $this->assertNull($caisse->report_status); // le bouton manuel reste disponible
    }

    public function test_en_pro_le_rapport_part_tout_seul(): void
    {
        $this->formule('pro');

        $this->assertSame('sent', $this->cloturer()->report_status);
        Http::assertSentCount(1);
    }

    public function test_pendant_l_essai_le_rapport_automatique_est_inclus(): void
    {
        $this->formule('basic');
        $this->maquis->update(['subscription_status' => 'trial', 'trial_ends_at' => now()->addDays(10)]);

        $this->assertTrue($this->maquis->fresh()->hasAutoReport());
        $this->assertSame('sent', $this->cloturer()->report_status);
    }

    public function test_la_tache_refuse_aussi_si_la_formule_ne_l_inclut_pas(): void
    {
        $this->formule('basic');
        $caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id, 'opened_by' => $this->jean->id,
            'opening_amount' => 0, 'opened_at' => now()->subHour(),
        ]);
        $caisse->close(0, $this->jean->id);

        (new SendRegisterReport($caisse->id))->handle(app(WhatsAppCloud::class));

        Http::assertNothingSent();
        $this->assertStringContainsString('non inclus dans la formule Basic', $caisse->fresh()->report_error);
    }

    public function test_le_patron_en_basic_voit_que_c_est_disponible_en_pro(): void
    {
        $this->formule('basic');

        $this->actingAs($this->patron)
            ->get(route('settings'))
            ->assertSee('Le rapport automatique est disponible à partir de la formule Pro.');
    }

    public function test_le_super_admin_choisit_les_formules_qui_l_incluent(): void
    {
        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();
        $basic = Plan::where('code', 'basic')->sole();
        $this->actingAs($admin);

        Livewire::test(PlansManage::class)
            ->set("form.{$basic->id}.includes_auto_report", true)
            ->call('save', $basic->id)
            ->assertHasNoErrors();

        $this->assertTrue($basic->fresh()->includes_auto_report);
    }

    public function test_la_page_d_accueil_montre_la_difference(): void
    {
        auth()->logout();

        $this->get(route('landing'))
            ->assertSee('Rapport du soir automatique sur WhatsApp')
            ->assertSee("Rapport du soir à envoyer d'un clic", false);
    }
}
