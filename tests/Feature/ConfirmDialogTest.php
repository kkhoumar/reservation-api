<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Vous êtes sûr ? » : la fenêtre aux couleurs de Maquis Express remplace
 * celle du téléphone (« maquis-express.onrender.com indique »).
 */
class ConfirmDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_fenetre_est_chargee_sur_les_pages_de_l_application(): void
    {
        $maquis = Restaurant::factory()->create();
        $patron = User::factory()->owner()->create(['restaurant_id' => $maquis->id]);
        User::factory()->create(['restaurant_id' => $maquis->id, 'role' => 'server', 'first_name' => 'Aya']);

        $this->actingAs($patron)->get(route('users.manage'))
            ->assertOk()
            ->assertSee('window.meConfirm', false)
            ->assertSee("Livewire.hook('directive.init'", false)
            ->assertSee('data-confirm-ok="Oui, il quitte le maquis"', false);
    }

    public function test_plus_aucune_fenetre_du_telephone_dans_le_code(): void
    {
        $left = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if ($file->isFile() && preg_match('/(?<![.\w])confirm\(/', file_get_contents($file->getPathname()))) {
                $left[] = $file->getFilename();
            }
        }

        $this->assertSame([], $left, 'Utilisez window.meConfirm() à la place de confirm().');
    }
}
