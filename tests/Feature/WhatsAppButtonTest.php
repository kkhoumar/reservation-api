<?php

namespace Tests\Feature;

use App\Livewire\Admin\PlatformSettings;
use App\Models\PlatformSetting;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\SupportContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bouton WhatsApp du service client : numéro réglé par le Super Admin,
 * visible sur l'accueil, la connexion et les écrans du patron et du gérant,
 * jamais sur les écrans de service.
 */
class WhatsAppButtonTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['maquis.billing.whatsapp' => null]);
        $this->maquis = Restaurant::factory()->create(['name' => 'Chez Koffi']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin;
    }

    public function test_sans_numero_le_bouton_est_cache(): void
    {
        $this->assertNull(SupportContact::whatsappUrl('Bonjour'));
        $this->get(route('landing'))->assertOk()->assertDontSee('me-wa', false);
    }

    public function test_le_super_admin_regle_le_numero(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(PlatformSettings::class)
            ->set('whatsapp', '07 12 34 56 78')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('0712345678', PlatformSetting::read(PlatformSetting::SUPPORT_WHATSAPP));
        $this->assertSame('https://wa.me/2250712345678?text=Bonjour', SupportContact::whatsappUrl('Bonjour'));

        Livewire::test(PlatformSettings::class)->set('whatsapp', '12345')->call('save')->assertHasErrors('whatsapp');
        $this->assertSame('0712345678', PlatformSetting::read(PlatformSetting::SUPPORT_WHATSAPP));
    }

    public function test_le_numero_du_super_admin_passe_avant_celui_du_serveur(): void
    {
        config(['maquis.billing.whatsapp' => '0700000000']);
        $this->assertSame('0700000000', SupportContact::whatsappNumber());

        PlatformSetting::write(PlatformSetting::SUPPORT_WHATSAPP, '0711111111');
        $this->assertSame('0711111111', SupportContact::whatsappNumber());
    }

    public function test_seul_le_super_admin_ouvre_les_reglages(): void
    {
        $patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);

        // Un patron est renvoyé ailleurs : il ne voit jamais la page des réglages
        $this->actingAs($patron)->get(route('admin.settings'))->assertRedirect()->assertDontSee('WhatsApp du service client');

        Livewire::actingAs($patron)->test(PlatformSettings::class)->assertForbidden();
        $this->assertNull(PlatformSetting::read(PlatformSetting::SUPPORT_WHATSAPP));
        $this->actingAs($this->admin())->get(route('admin.settings'))->assertOk()->assertSee('WhatsApp du service client');
    }

    public function test_le_bouton_apparait_sur_l_accueil_et_la_connexion(): void
    {
        PlatformSetting::write(PlatformSetting::SUPPORT_WHATSAPP, '0712345678');

        $this->get(route('landing'))->assertOk()->assertSee('https://wa.me/2250712345678', false)->assertSee('me-wa', false);
        $this->get(route('login'))->assertOk()->assertSee('me-wa', false);
    }

    public function test_le_patron_le_voit_avec_le_nom_du_maquis_mais_pas_le_personnel(): void
    {
        PlatformSetting::write(PlatformSetting::SUPPORT_WHATSAPP, '0712345678');

        $patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Koffi']);
        $this->actingAs($patron)->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('me-wa', false)
            ->assertSee(rawurlencode('Chez Koffi'), false);

        $caissier = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($caissier)->get(route('cash-register.manage'))->assertOk()->assertDontSee('me-wa', false);
    }
}
