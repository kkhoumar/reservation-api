<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Application installable (PWA) : manifeste, icônes, service worker,
 * point d'entrée /app, et plus de boucle de redirection une fois connecté.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_manifeste_decrit_l_application(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'name' => 'Maquis Express',
                'start_url' => '/app',
                'display' => 'standalone',
                'theme_color' => '#0B4D3C',
                'lang' => 'fr',
            ]);

        // Chrome exige des icônes 192 et 512 (et une icône adaptative)
        $icons = collect($response->json('icons'));
        $this->assertTrue($icons->contains('sizes', '192x192'));
        $this->assertTrue($icons->contains('purpose', 'maskable'));
        foreach ($icons as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')), "Icône manquante : {$icon['src']}");
        }
    }

    public function test_le_service_worker_et_la_page_hors_ligne_existent(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));

        // Les pages et les données ne sont jamais mises en cache
        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("request.method !== 'GET'", $sw);
        $this->assertStringContainsString("request.mode === 'navigate'", $sw);
    }

    public function test_toutes_les_pages_declarent_l_application(): void
    {
        $this->get(route('landing'))->assertSee(route('pwa.manifest'), false);
        $this->get(route('login'))->assertSee(route('pwa.manifest'), false)->assertSee("Installer l'application", false);

        $patron = User::factory()->owner()->create();
        $this->actingAs($patron)->get(route('dashboard.index'))
            ->assertSee(route('pwa.manifest'), false)
            ->assertSee('/sw.js', false);
    }

    public function test_le_point_d_entree_envoie_chacun_au_bon_endroit(): void
    {
        $maquis = Restaurant::factory()->create();

        $this->get('/app')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->owner()->create(['restaurant_id' => $maquis->id]))
            ->get('/app')->assertRedirect(route('dashboard.index'));

        $this->actingAs(User::factory()->server()->create(['restaurant_id' => $maquis->id]))
            ->get('/app')->assertRedirect(route('tables.index'));

        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->actingAs($admin)->get('/app')->assertRedirect(route('admin.restaurants'));
    }

    public function test_deja_connecte_la_page_de_connexion_ne_boucle_plus(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('login'))
            ->assertRedirect('/app');
    }
}
