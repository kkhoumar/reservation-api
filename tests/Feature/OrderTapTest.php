<?php

namespace Tests\Feature;

use App\Livewire\NewOrder;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prise de commande au doigt : chaque touche ajoute 1, les touches d'un même
 * produit font une seule ligne, « − » enlève 1. Un article parti en cuisine
 * ne peut être diminué que par le comptoir ou la direction, et c'est noté.
 */
class OrderTapTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $caissier;
    private User $serveuse;
    private Product $flag;
    private RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Service à table, paiement après : la cuisine reçoit les bons
        $this->maquis = Restaurant::factory()->create(['service_mode' => 'both', 'payment_timing' => 'after']);
        $this->caissier = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->serveuse = User::factory()->server()->create(['restaurant_id' => $this->maquis->id]);
        $this->flag = Product::factory()->create([
            'restaurant_id' => $this->maquis->id, 'name' => 'Flag', 'selling_price' => 1000, 'stock_quantity' => 10,
        ]);
        $this->table = RestaurantTable::factory()->create(['restaurant_id' => $this->maquis->id]);
    }

    public function test_les_touches_d_un_meme_produit_font_une_seule_ligne(): void
    {
        $this->actingAs($this->caissier);

        Livewire::test(NewOrder::class)
            ->call('addProduct', $this->flag->id)
            ->call('addProduct', $this->flag->id)
            ->call('addProduct', $this->flag->id, 2, 'a1');

        $line = OrderItem::sole();
        $this->assertSame([4, 4000], [(int) $line->quantity, (int) $line->total]);
        $this->assertSame(4000, (int) Order::sole()->total);
        $this->assertSame(6, $this->flag->fresh()->stock_quantity);
    }

    public function test_touches_regroupees_refusees_si_le_stock_ne_suffit_pas(): void
    {
        $this->actingAs($this->caissier);

        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id, 11, 'a1');
        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id, 0, 'a2');

        $this->assertSame(0, OrderItem::count());
        $this->assertSame(10, $this->flag->fresh()->stock_quantity);
    }

    public function test_moins_enleve_un_article_et_remet_le_stock(): void
    {
        $this->actingAs($this->caissier);

        $component = Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id, 3, 'a1');
        $line = OrderItem::sole();

        $component->call('decrementItem', $line->id);
        $this->assertSame(2, (int) $line->fresh()->quantity);
        $this->assertSame(2000, (int) Order::sole()->total);
        $this->assertSame(8, $this->flag->fresh()->stock_quantity);

        $component->call('decrementItem', $line->id, 2, 'r1');
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, (int) Order::sole()->total);
        $this->assertSame(10, $this->flag->fresh()->stock_quantity);

        // Simple correction avant l'envoi en cuisine : rien au journal
        $this->assertSame(0, ActivityLog::where('action', 'item_removed')->count());
    }

    public function test_plus_d_une_ligne_ajoute_le_meme_produit(): void
    {
        $this->actingAs($this->caissier);

        $component = Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id);
        $component->call('incrementItem', OrderItem::sole()->id);

        $this->assertSame(2, (int) OrderItem::sole()->quantity);
        $this->assertSame(8, $this->flag->fresh()->stock_quantity);
    }

    public function test_apres_la_cuisine_un_ajout_fait_une_nouvelle_ligne(): void
    {
        $this->actingAs($this->serveuse);

        $component = Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->call('addProduct', $this->flag->id, 2, 'a1')
            ->call('sendToKitchen')
            ->call('addProduct', $this->flag->id);

        $lines = OrderItem::orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertNotNull($lines[0]->sent_at);
        $this->assertNull($lines[1]->sent_at);
        $this->assertSame([2, 1], [(int) $lines[0]->quantity, (int) $lines[1]->quantity]);

        // La serveuse peut corriger la nouvelle ligne, pas celle partie en cuisine
        $component->call('decrementItem', $lines[1]->id);
        $this->assertSame(1, OrderItem::count());

        $component->call('decrementItem', $lines[0]->id);
        $this->assertSame(2, (int) $lines[0]->fresh()->quantity);
        $this->assertSame(8, $this->flag->fresh()->stock_quantity);
        $this->assertSame(0, ActivityLog::where('action', 'item_removed')->count());
    }

    public function test_le_comptoir_peut_diminuer_un_article_parti_en_cuisine_et_c_est_note(): void
    {
        $this->actingAs($this->serveuse);
        Livewire::test(NewOrder::class, ['table' => $this->table->id])
            ->call('addProduct', $this->flag->id, 2, 'a1')
            ->call('sendToKitchen');

        $line = OrderItem::sole();

        $this->actingAs($this->caissier);
        Livewire::test(NewOrder::class, ['table' => $this->table->id])->call('decrementItem', $line->id);

        $this->assertSame(1, (int) $line->fresh()->quantity);
        $this->assertSame(9, $this->flag->fresh()->stock_quantity);

        $log = ActivityLog::where('action', 'item_removed')->sole();
        $this->assertStringContainsString("1 × Flag", $log->description);
        $this->assertSame('warning', $log->severity());
    }

    public function test_une_ligne_d_un_autre_bon_ne_peut_pas_etre_diminuee(): void
    {
        $this->actingAs($this->caissier);

        Livewire::test(NewOrder::class)->call('addProduct', $this->flag->id, 2, 'a1');
        $autreLigne = OrderItem::sole();

        Livewire::test(NewOrder::class)
            ->call('addProduct', $this->flag->id)
            ->call('decrementItem', $autreLigne->id)
            ->call('incrementItem', $autreLigne->id);

        $this->assertSame(2, (int) $autreLigne->fresh()->quantity);
    }

    public function test_l_ecran_de_commande_s_affiche_avec_les_pastilles(): void
    {
        $this->actingAs($this->caissier);

        Livewire::test(NewOrder::class)
            ->call('addProduct', $this->flag->id, 3, 'a1')
            ->assertSee('Commande en cours')
            ->assertSee('Flag')
            ->assertSee('3 000 FCFA');
    }
}
