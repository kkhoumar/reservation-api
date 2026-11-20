<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use App\Support\PhotoStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Sur le serveur de test, les photos vont sur Supabase Storage (PHOTOS_DISK) :
 * enregistrement, affichage et suppression passent par ce disque.
 */
class PhotoDiskTest extends TestCase
{
    use RefreshDatabase;

    private function photo(): string
    {
        $img = imagecreatetruecolor(200, 200);
        ob_start();
        imagejpeg($img, null, 80);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    public function test_les_photos_vont_sur_le_disque_configure(): void
    {
        Storage::fake('local');
        Storage::fake('supabase');
        config(['filesystems.photos_disk' => 'supabase']);

        [$path, $error] = PhotoStore::store($this->photo(), 'avatars', 'u1');

        $this->assertNull($error);
        Storage::disk('supabase')->assertExists($path);
        Storage::disk('local')->assertMissing($path);

        $maquis = Restaurant::factory()->create();
        $koffi = User::factory()->owner()->create(['restaurant_id' => $maquis->id, 'avatar_path' => $path]);

        $this->actingAs($koffi)->get(route('avatar.show', $koffi))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        PhotoStore::delete($path);
        Storage::disk('supabase')->assertMissing($path);
    }
}
