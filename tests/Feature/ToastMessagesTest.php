<?php

namespace Tests\Feature;

use App\Livewire\ProductsManage;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\Toast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Messages en bas de l'écran : précis (le nom du produit), un pour chaque
 * action, et « Annuler » quand on peut revenir en arrière.
 */
class ToastMessagesTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private Category $boissons;

    protected function setUp(): void
    {
        parent::setUp();
        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons', 'kind' => 'drink']);
        $this->actingAs($this->patron);
    }

    public function test_chaque_message_a_son_identifiant_et_peut_proposer_annuler(): void
    {
        Toast::success('Flag est ajouté.', ['call' => 'restoreProduct', 'args' => [3]]);
        $first = session('toast_id');

        $this->assertSame('Flag est ajouté.', session('success'));
        $this->assertSame(['call' => 'restoreProduct', 'args' => [3]], session('toast_undo'));

        // Le même texte une deuxième fois = un nouveau message
        Toast::success('Flag est ajouté.');
        $this->assertNotSame($first, session('toast_id'));
        $this->assertNull(session('toast_undo'));

        Toast::error('Oups.');
        $this->assertSame('Oups.', session('error'));
    }

    public function test_le_message_dit_le_nom_du_produit(): void
    {
        Livewire::test(ProductsManage::class)
            ->call('openCreateForm')
            ->set('name', 'Castel')
            ->set('categoryId', $this->boissons->id)
            ->set('sellingPrice', '700')
            ->set('costPrice', '450')
            ->set('stockQuantity', '24')
            ->call('saveProduct')
            ->assertSee('« Castel » est ajouté à la carte.', false)
            ->assertSee('flash-src', false);
    }

    public function test_desactiver_propose_annuler_et_annuler_remet_en_vente(): void
    {
        $flag = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id, 'name' => 'Flag', 'is_available' => true]);

        $page = Livewire::test(ProductsManage::class)->call('toggleAvailability', $flag->id)
            ->assertSee("« Flag » n'est plus en vente.");
        $this->assertFalse($flag->fresh()->is_available);
        // Le message porte le bouton « Annuler », qui rappelle la même action
        $page->assertSeeHtml('\u0022undo\u0022:{\u0022call\u0022:\u0022toggleAvailability\u0022,\u0022args\u0022:[' . $flag->id . ']}');

        // « Annuler » rappelle la même action
        $page->call('toggleAvailability', $flag->id)->assertSee('« Flag » est de nouveau en vente.', false);
        $this->assertTrue($flag->fresh()->is_available);
    }

    public function test_retirer_un_produit_vendu_propose_annuler(): void
    {
        $flag = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id, 'name' => 'Flag']);
        $order = Order::factory()->create(['restaurant_id' => $this->maquis->id, 'user_id' => $this->patron->id, 'status' => 'paid']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $flag->id]);
        Order::query()->update(['status' => 'paid']);

        $page = Livewire::test(ProductsManage::class)->call('deleteProduct', $flag->id)
            ->assertSeeHtml('\u0022undo\u0022:{\u0022call\u0022:\u0022restoreProduct\u0022,\u0022args\u0022:[' . $flag->id . ']}');
        $this->assertNotNull($flag->fresh()->archived_at);

        $page->call('restoreProduct', $flag->id);
        $this->assertNull($flag->fresh()->archived_at);
    }

    public function test_les_actions_muettes_ont_maintenant_un_message(): void
    {
        $garba = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $this->boissons->id, 'name' => 'Garba', 'track_stock' => false]);

        Livewire::test(ProductsManage::class)
            ->call('toggleSoldOut', $garba->id)
            ->assertSee("« Garba » : fini pour aujourd'hui. Il revient tout seul demain.")
            ->call('setCategoryKind', $this->boissons->id, 'food')
            ->assertSee('« Boissons » est rangée dans Nourriture.', false);
    }

    public function test_une_erreur_apres_redirection_s_affiche_sur_la_page_suivante(): void
    {
        $serveuse = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'server']);

        $this->actingAs($serveuse)->get(route('users.manage'))->assertRedirect();
        $this->followingRedirects()->actingAs($serveuse)->get(route('users.manage'))
            ->assertSee("Cette page n'est pas accessible avec votre rôle actuel.");
    }
}
