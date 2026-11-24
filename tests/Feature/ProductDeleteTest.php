<?php

namespace Tests\Feature;

use App\Livewire\NewOrder;
use App\Livewire\ProductsManage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * « Supprimer » un produit : jamais vendu → effacé ; déjà vendu → rangé dans
 * les archives, sans toucher aux anciennes ventes. On peut le remettre.
 */
class ProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private Product $flag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create(['service_mode' => 'both', 'payment_timing' => 'after']);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->flag = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'name' => 'Flag', 'selling_price' => 1000, 'stock_quantity' => 10,
        ]);
    }

    /** Une vente de Flag, payée. */
    private function sellFlag(string $status = 'paid'): OrderItem
    {
        $this->actingAs($this->patron);
        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id);
        Order::query()->update(['status' => $status]);

        return OrderItem::sole();
    }

    public function test_un_produit_jamais_vendu_est_efface(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(ProductsManage::class)
            ->call('deleteProduct', $this->flag->id)
            ->assertDontSee('Voir les produits retirés');

        $this->assertNull(Product::find($this->flag->id));
    }

    public function test_un_produit_deja_vendu_est_range_et_ses_ventes_restent(): void
    {
        $line = $this->sellFlag();

        Livewire::test(ProductsManage::class)
            ->call('deleteProduct', $this->flag->id)
            ->assertSee('Voir les produits retirés (1)');

        $flag = $this->flag->fresh();
        $this->assertNotNull($flag->archived_at);
        $this->assertFalse($flag->is_available);
        $this->assertNotNull($line->fresh(), 'la vente est gardée');
        $this->assertSame(1000, (int) Order::sole()->total);

        // Plus proposé à la vente
        Livewire::test(NewOrder::class)->assertDontSee('Ajouter Flag');
    }

    public function test_un_produit_dans_une_commande_pas_payee_reste(): void
    {
        $this->sellFlag('served');

        Livewire::test(ProductsManage::class)->call('deleteProduct', $this->flag->id)->assertSee('pas encore payée');

        $this->assertNull($this->flag->fresh()->archived_at);
    }

    public function test_remettre_un_produit_archive(): void
    {
        $this->sellFlag();

        Livewire::test(ProductsManage::class)
            ->call('deleteProduct', $this->flag->id)
            ->call('restoreProduct', $this->flag->id)
            ->assertDontSee('Voir les produits retirés');

        $flag = $this->flag->fresh();
        $this->assertNull($flag->archived_at);
        $this->assertTrue($flag->is_available);
    }

    public function test_un_autre_maquis_ne_peut_pas_supprimer(): void
    {
        $autre = User::factory()->owner()->create(['restaurant_id' => Restaurant::factory()->create()->id]);
        $this->actingAs($autre);

        try {
            Livewire::test(ProductsManage::class)->call('deleteProduct', $this->flag->id);
        } catch (\Throwable) {
        }

        $this->assertNotNull(Product::withoutGlobalScopes()->find($this->flag->id));
    }
}
