<?php

namespace Tests\Feature;

use App\Livewire\ExpensesList;
use App\Livewire\StockManage;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Photo du reçu obligatoire : sans preuve, une dépense inventée
 * permettait de sortir de l'argent de la caisse.
 */
class ExpenseReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $gerant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->gerant = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Ali']);
        $this->openRegister($this->maquis, $this->gerant);
    }

    private function photo(): string
    {
        $img = imagecreatetruecolor(300, 400);
        imagefill($img, 0, 0, imagecolorallocate($img, 240, 240, 230));
        ob_start();
        imagejpeg($img, null, 80);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    public function test_une_depense_sans_photo_du_recu_est_refusee(): void
    {
        $this->actingAs($this->gerant);

        Livewire::test(ExpensesList::class)
            ->set('category', 'other')
            ->set('amount', 5000)
            ->set('description', 'Achat glace')
            ->call('addExpense')
            ->assertHasErrors(['receiptPhoto' => 'required']);

        $this->assertSame(0, Expense::count());
    }

    public function test_une_depense_avec_recu_est_enregistree_et_le_patron_voit_le_recu(): void
    {
        $this->actingAs($this->gerant);

        Livewire::test(ExpensesList::class)
            ->set('category', 'other')
            ->set('amount', 5000)
            ->set('description', 'Achat glace')
            ->set('receiptPhoto', $this->photo())
            ->call('addExpense')
            ->assertHasNoErrors()
            ->assertSet('receiptPhoto', '')
            ->assertSee('Voir le reçu');

        $expense = Expense::sole();
        Storage::disk('local')->assertExists($expense->receipt_path);
        $this->assertStringContainsString('Reçu joint', ActivityLog::where('action', 'expense')->value('description'));

        $this->actingAs($this->patron)->get(route('expenses.receipt', $expense))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $autre = User::factory()->owner()->create(['restaurant_id' => Restaurant::factory()->create()->id]);
        $this->actingAs($autre)->get(route('expenses.receipt', $expense))->assertNotFound();
    }

    public function test_une_livraison_payee_avec_la_caisse_demande_la_photo_du_bon(): void
    {
        $boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Boissons']);
        $biere = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $boissons->id, 'selling_price' => 1000, 'cost_price' => 600, 'stock_quantity' => 0]);
        $this->actingAs($this->gerant);

        $screen = Livewire::test(StockManage::class)
            ->call('setTab', 'delivery')
            ->set("deliveryLines.{$biere->id}.quantity", '24')
            ->set("deliveryLines.{$biere->id}.unit_cost", '600')
            ->set('paidFromRegister', true)
            ->call('saveDelivery')
            ->assertHasErrors('deliveryPhoto');

        $this->assertSame(0, Expense::count());
        $this->assertSame(0, $biere->fresh()->stock_quantity);

        $screen->set('deliveryPhoto', $this->photo())
            ->call('saveDelivery')
            ->assertHasNoErrors();

        $expense = Expense::sole();
        $this->assertSame(24 * 600, $expense->amount);
        $this->assertNotNull($expense->receipt_path);
        $this->assertSame(24, $biere->fresh()->stock_quantity);
    }

    public function test_une_livraison_payee_autrement_ne_demande_pas_de_photo(): void
    {
        $boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id]);
        $biere = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $boissons->id, 'stock_quantity' => 0]);
        $this->actingAs($this->gerant);

        Livewire::test(StockManage::class)
            ->call('setTab', 'delivery')
            ->set("deliveryLines.{$biere->id}.quantity", '12')
            ->call('saveDelivery')
            ->assertHasNoErrors();

        $this->assertSame(12, $biere->fresh()->stock_quantity);
        $this->assertSame(0, Expense::count());
    }
}
