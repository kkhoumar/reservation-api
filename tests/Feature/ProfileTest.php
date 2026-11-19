<?php

namespace Tests\Feature;

use App\Livewire\Profile;
use App\Livewire\UsersManage;
use App\Models\ActivityLog;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Mon profil : photo, nom, numéro, mot de passe. Les photos ne sont
 * visibles que par le même maquis.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $aya;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya']);
    }

    /** Une vraie petite photo JPEG, comme celle que prépare le téléphone. */
    private function jpegDataUrl(int $size = 64): string
    {
        $img = imagecreatetruecolor($size, $size);
        imagefill($img, 0, 0, imagecolorallocate($img, 11, 77, 60));
        ob_start();
        imagejpeg($img, null, 85);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    public function test_chaque_compte_a_sa_page_profil(): void
    {
        $this->actingAs($this->aya)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Mon profil')
            ->assertSee('Serveuse')
            ->assertSee('Ajouter une photo');
    }

    public function test_ajouter_puis_retirer_sa_photo(): void
    {
        $this->actingAs($this->aya);

        Livewire::test(Profile::class)
            ->call('savePhoto', $this->jpegDataUrl())
            ->assertReturned(null);

        $path = $this->aya->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        Livewire::test(Profile::class)->call('removePhoto');

        $this->assertNull($this->aya->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_un_fichier_qui_n_est_pas_une_photo_est_refuse(): void
    {
        $this->actingAs($this->aya);

        Livewire::test(Profile::class)
            ->call('savePhoto', 'data:image/jpeg;base64,' . base64_encode('<?php echo "pirate"; ?>'))
            ->assertReturned("Ce fichier n'est pas une photo valable. Choisissez-en une autre.")
            ->call('savePhoto', 'data:text/html;base64,PGgxPg==')
            ->assertReturned('Choisissez une photo (JPEG, PNG ou WebP).');

        $this->assertNull($this->aya->fresh()->avatar_path);
    }

    public function test_la_photo_n_est_visible_que_par_le_meme_maquis(): void
    {
        $this->actingAs($this->aya);
        Livewire::test(Profile::class)->call('savePhoto', $this->jpegDataUrl());
        $url = $this->aya->fresh()->avatarUrl();

        $this->actingAs($this->patron)->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $autre = User::factory()->owner()->create(['restaurant_id' => Restaurant::factory()->create()->id]);
        $this->actingAs($autre)->get($url)->assertNotFound();
    }

    public function test_modifier_ses_informations(): void
    {
        $this->actingAs($this->aya);

        Livewire::test(Profile::class)
            ->set('firstName', 'Aya Marie')
            ->set('phone', '07 98 76 54 32')
            ->call('saveInfo')
            ->assertHasNoErrors();

        $this->assertSame(['Aya Marie', '0798765432'], [$this->aya->fresh()->first_name, $this->aya->fresh()->phone]);

        Livewire::test(Profile::class)
            ->set('phone', $this->patron->phone)
            ->call('saveInfo')
            ->assertHasErrors(['phone' => 'unique']);
    }

    public function test_changer_son_mot_de_passe(): void
    {
        $this->actingAs($this->aya);

        Livewire::test(Profile::class)
            ->set('currentPassword', 'mauvais')
            ->set('password', 'nouveau-mot-2026')
            ->set('passwordConfirmation', 'nouveau-mot-2026')
            ->call('savePassword')
            ->assertHasErrors('currentPassword')
            ->set('currentPassword', 'password')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nouveau-mot-2026', $this->aya->fresh()->password));
        $this->assertSame('password_changed', ActivityLog::sole()->action);
    }

    public function test_le_patron_met_la_photo_d_un_employe_mais_pas_l_inverse(): void
    {
        $this->actingAs($this->patron);
        Livewire::test(UsersManage::class)
            ->call('saveMemberPhoto', $this->aya->id, $this->jpegDataUrl())
            ->assertReturned(null);
        $this->assertNotNull($this->aya->fresh()->avatar_path);

        $this->actingAs($this->aya)->get(route('users.manage'))->assertRedirect();
    }
}
