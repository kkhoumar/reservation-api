<?php

namespace Tests\Feature;

use App\Livewire\NewOrder;
use App\Livewire\ProductsManage;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\ProductIcon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Petites images des produits : dessin choisi tout seul d'après le nom,
 * dessin choisi par le patron, ou vraie photo (visible seulement dans son maquis).
 */
class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private Category $boissons;
    private Category $cuisine;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Bières', 'kind' => 'drink']);
        $this->cuisine = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Cuisine', 'kind' => 'food']);
    }

    private function jpeg(): string
    {
        $img = imagecreatetruecolor(320, 320);
        imagefill($img, 0, 0, imagecolorallocate($img, 30, 110, 60));
        ob_start();
        imagejpeg($img, null, 80);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    private function produit(string $name, Category $category, array $extra = []): Product
    {
        return Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $category->id, 'name' => $name] + $extra);
    }

    public function test_le_dessin_est_choisi_tout_seul_d_apres_le_nom(): void
    {
        $cas = [
            ['Flag', 'Bières', 'drink', 'biere_verte'],
            ['Bock', 'Bières', 'drink', 'biere_brune'],
            ['Beaufort 65 cl', 'Bières', 'drink', 'grande'],
            ['Castel GM', 'Boissons', 'drink', 'grande'],
            ['Coca canette', 'Sucreries', 'drink', 'canette'],
            ['Booster Tequila', 'Boissons', 'drink', 'canette'],
            ['Fanta', 'Sucreries', 'drink', 'sucrerie'],
            ['Eau minérale', 'Sucreries', 'drink', 'eau'],
            ['Bissap', 'Boissons', 'drink', 'jus'],
            ['Vin rouge', 'Boissons', 'drink', 'vin'],
            ['Whisky (dose)', 'Liqueurs', 'drink', 'liqueur'],
            ['Gingembre', 'Boissons', 'drink', 'jus'],
            ['Poulet braisé', 'Cuisine', 'food', 'plat'],
            ['Eau de coco glacée', 'Cuisine', 'food', 'plat'], // un plat ne devient jamais une bouteille
            ['Super Mix', 'Bières', 'drink', 'biere_brune'],   // inconnu : on regarde la catégorie
            ['Super Mix', 'Divers', 'drink', 'boisson'],
        ];

        foreach ($cas as [$name, $category, $kind, $attendu]) {
            $this->assertSame($attendu, ProductIcon::guess($name, $category, $kind), "« {$name} » ({$category})");
        }
    }

    public function test_le_patron_choisit_un_autre_dessin_et_peut_revenir_a_auto(): void
    {
        $this->actingAs($this->patron);
        $p = $this->produit('Mystère', $this->boissons, ['selling_price' => 700, 'cost_price' => 400]);

        Livewire::test(ProductsManage::class)
            ->call('editProduct', $p->id)
            ->set('icon', 'canette')
            ->call('saveProduct')
            ->assertHasNoErrors();
        $this->assertSame('canette', $p->fresh()->icon);
        $this->assertSame('canette', $p->fresh()->iconKey());

        Livewire::test(ProductsManage::class)
            ->call('editProduct', $p->id)
            ->assertSet('icon', 'canette')
            ->set('icon', '')
            ->call('saveProduct');
        $this->assertNull($p->fresh()->icon);
        $this->assertSame('biere_brune', $p->fresh()->iconKey());
    }

    public function test_un_dessin_inconnu_est_refuse(): void
    {
        $this->actingAs($this->patron);
        $p = $this->produit('Flag', $this->boissons, ['selling_price' => 700, 'cost_price' => 400]);

        Livewire::test(ProductsManage::class)
            ->call('editProduct', $p->id)
            ->set('icon', '<script>')
            ->call('saveProduct')
            ->assertHasErrors('icon');
        $this->assertNull($p->fresh()->icon);
    }

    public function test_la_photo_est_enregistree_affichee_puis_retiree(): void
    {
        $this->actingAs($this->patron);
        $p = $this->produit('Flag', $this->boissons);

        $erreur = Livewire::test(ProductsManage::class)->call('saveProductPhoto', $p->id, $this->jpeg())->get('effects')['returns'][0] ?? null;
        $p->refresh();
        $this->assertNull($erreur);
        $this->assertNotNull($p->photo);
        Storage::disk('local')->assertExists($p->photo);

        // Toute l'équipe du maquis la voit…
        $this->get($p->photoUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $serveuse = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'server']);
        $this->actingAs($serveuse)->get($p->photoUrl())->assertOk();
        // … pas un autre maquis
        $autre = User::factory()->owner()->create(['restaurant_id' => Restaurant::factory()->create()->id]);
        $this->actingAs($autre)->get(route('products.photo', $p))->assertNotFound();

        // Retirer : le fichier part, le dessin revient
        $this->actingAs($this->patron);
        $chemin = $p->photo;
        Livewire::test(ProductsManage::class)->call('removeProductPhoto', $p->id);
        $this->assertNull($p->fresh()->photo);
        Storage::disk('local')->assertMissing($chemin);
    }

    public function test_un_faux_fichier_n_est_pas_accepte_comme_photo(): void
    {
        $this->actingAs($this->patron);
        $p = $this->produit('Flag', $this->boissons);

        Livewire::test(ProductsManage::class)->call('saveProductPhoto', $p->id, 'data:image/jpeg;base64,' . base64_encode('pas une image'));
        $this->assertNull($p->fresh()->photo);
    }

    public function test_on_ne_touche_pas_a_la_photo_d_un_produit_d_un_autre_maquis(): void
    {
        $autreMaquis = Restaurant::factory()->create();
        $autreCat = Category::factory()->create(['restaurant_id' => $autreMaquis->id]);
        $autreProduit = Product::factory()->create(['restaurant_id' => $autreMaquis->id, 'category_id' => $autreCat->id]);

        $this->actingAs($this->patron);
        try {
            Livewire::test(ProductsManage::class)->call('saveProductPhoto', $autreProduit->id, $this->jpeg());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        }
        $this->assertNull($autreProduit->fresh()->photo);
    }

    public function test_les_images_s_affichent_sur_le_menu_et_la_prise_de_commande(): void
    {
        $this->actingAs($this->patron);
        $this->produit('Flag', $this->boissons, ['stock_quantity' => 10]);
        $this->produit('Poulet braisé', $this->cuisine, ['track_stock' => false]);

        Livewire::test(ProductsManage::class)->assertSeeHtml('#pi-biere')->assertSeeHtml('#pi-plat');
        $table = \App\Models\RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id, 'label' => 'Table 1']);
        Livewire::test(NewOrder::class, ['table' => $table->id])->assertSeeHtml('#pi-biere')->assertSeeHtml('#pi-plat');
        $this->get(route('products.manage'))->assertOk()->assertSee('id="pi-biere"', false);
    }
}
