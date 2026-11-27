<?php

namespace Tests\Feature;

use App\Livewire\ExpensesList;
use App\Livewire\ProductsManage;
use App\Livewire\RestaurantSettings;
use App\Livewire\StockManage;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\PhotoOrigin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Photos choisies dans le téléphone (capture Wave, facture WhatsApp…) :
 * acceptées pour les reçus, les bons de livraison et les produits, avec
 * l'origine et la date notées pour que le patron repère une vieille photo.
 */
class PhotoFromPhoneTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $caissier;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->caissier = User::factory()->create(['restaurant_id' => $this->maquis->id, 'role' => 'cashier', 'first_name' => 'Koffi']);
        $this->openRegister($this->maquis, $this->caissier);
    }

    private function photo(): string
    {
        $img = imagecreatetruecolor(300, 400);
        imagefill($img, 0, 0, imagecolorallocate($img, 240, 240, 230));
        ob_start();
        imagejpeg($img, null, 80);

        return 'data:image/jpeg;base64,' . base64_encode(ob_get_clean());
    }

    private function depense(int $amount, array $photo = []): \Livewire\Features\SupportTesting\Testable
    {
        $screen = Livewire::test(ExpensesList::class)
            ->set('category', 'other')
            ->set('amount', $amount)
            ->set('description', 'Gaz');

        foreach ($photo as $k => $v) {
            $screen->set($k, $v);
        }

        return $screen->call('addExpense');
    }

    public function test_une_capture_choisie_dans_le_telephone_est_acceptee_avec_sa_date(): void
    {
        $this->actingAs($this->caissier);
        $hier = now()->subDay()->startOfMinute();

        $this->depense(6500, ['receiptPhoto' => $this->photo(), 'receiptPhotoSource' => 'gallery', 'receiptPhotoDate' => $hier->getTimestampMs()])
            ->assertHasNoErrors()
            ->assertSet('receiptPhotoSource', '')
            ->assertSee('Du téléphone, photo du');

        $expense = Expense::sole();
        $this->assertSame(PhotoOrigin::GALLERY, $expense->receipt_source);
        $this->assertTrue($expense->receipt_taken_at->equalTo($hier));
        $this->assertFalse($expense->receiptLooksOld());
        $this->assertStringContainsString('choisi dans le téléphone', ActivityLog::where('action', 'expense')->value('description'));
    }

    public function test_une_vieille_photo_est_signalee_au_patron(): void
    {
        $this->actingAs($this->caissier);

        $this->depense(6500, ['receiptPhoto' => $this->photo(), 'receiptPhotoSource' => 'gallery', 'receiptPhotoDate' => now()->subDays(10)->getTimestampMs()])
            ->assertHasNoErrors();

        $this->assertTrue(Expense::sole()->receiptLooksOld());
        $this->actingAs($this->patron);
        Livewire::test(ExpensesList::class)->assertSee('Photo ancienne');
    }

    public function test_une_photo_prise_sur_place_est_marquee(): void
    {
        $this->actingAs($this->caissier);

        // Le téléphone peut mentir sur la date : prise sur place = l'heure du serveur
        $this->depense(3000, ['receiptPhoto' => $this->photo(), 'receiptPhotoSource' => 'camera', 'receiptPhotoDate' => now()->subYear()->getTimestampMs()])
            ->assertHasNoErrors()
            ->assertSee('Prise sur place');

        $expense = Expense::sole();
        $this->assertSame(PhotoOrigin::CAMERA, $expense->receipt_source);
        $this->assertTrue($expense->receipt_taken_at->isToday());
    }

    public function test_origine_inconnue_ou_date_absurde_n_affiche_rien_de_faux(): void
    {
        $this->assertSame(['receipt_source' => null, 'receipt_taken_at' => null], PhotoOrigin::fromClient('<script>', 0));
        $this->assertNull(PhotoOrigin::fromClient('gallery', 'abc')['receipt_taken_at']);
        $this->assertNull(PhotoOrigin::fromClient('gallery', 12)['receipt_taken_at']);
        $this->assertTrue(PhotoOrigin::fromClient('gallery', now()->addYear()->getTimestampMs())['receipt_taken_at']->lte(now()));
    }

    public function test_la_photo_reste_obligatoire_par_defaut_meme_pour_200_f(): void
    {
        $this->actingAs($this->caissier);
        $this->depense(200)->assertHasErrors('receiptPhoto');
        $this->assertSame(0, Expense::count());
    }

    public function test_le_patron_rend_la_photo_facultative_pour_les_petites_depenses(): void
    {
        $this->actingAs($this->patron);
        Livewire::test(RestaurantSettings::class)
            ->assertSee('Photo du reçu des dépenses')
            ->set('receiptRequiredFrom', '2000')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(2000, $this->maquis->fresh()->receipt_required_from);

        $this->actingAs($this->caissier);
        Livewire::test(ExpensesList::class)->assertSee('Obligatoire dès 2 000 F');

        // 500 F : sans photo, accepté et noté dans le journal
        $this->depense(500)->assertHasNoErrors();
        $this->assertNull(Expense::sole()->receipt_path);
        $this->assertStringContainsString('Sans reçu', ActivityLog::where('action', 'expense')->value('description'));

        // 2 000 F et plus : la photo est demandée
        $this->depense(2000)->assertHasErrors('receiptPhoto');
        $this->assertSame(1, Expense::count());
    }

    public function test_un_seuil_invente_est_refuse(): void
    {
        $this->actingAs($this->patron);
        Livewire::test(RestaurantSettings::class)->set('receiptRequiredFrom', '999999')->call('save')->assertHasErrors('receiptRequiredFrom');
        $this->assertSame(0, $this->maquis->fresh()->receipt_required_from);
    }

    public function test_le_bon_de_livraison_venu_de_whatsapp_est_note(): void
    {
        $boissons = Category::factory()->create(['restaurant_id' => $this->maquis->id]);
        $biere = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $boissons->id, 'cost_price' => 600, 'selling_price' => 1000, 'stock_quantity' => 0]);
        $this->actingAs($this->patron);

        Livewire::test(StockManage::class)
            ->call('setTab', 'delivery')
            ->set("deliveryLines.{$biere->id}.quantity", '24')
            ->set("deliveryLines.{$biere->id}.unit_cost", '600')
            ->set('paidFromRegister', true)
            ->set('deliveryPhoto', $this->photo())
            ->set('deliveryPhotoSource', 'gallery')
            ->set('deliveryPhotoDate', now()->subHours(3)->getTimestampMs())
            ->call('saveDelivery')
            ->assertHasNoErrors()
            ->assertSet('deliveryPhotoSource', '');

        $expense = Expense::sole();
        $this->assertSame(PhotoOrigin::GALLERY, $expense->receipt_source);
        $this->assertNotNull($expense->receipt_taken_at);
    }

    public function test_les_deux_boutons_sont_proposes(): void
    {
        $this->actingAs($this->caissier);
        Livewire::test(ExpensesList::class)->assertSee('Prendre une photo')->assertSee('Choisir dans le téléphone');

        $this->actingAs($this->patron);
        $cat = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'kind' => 'drink']);
        $p = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'category_id' => $cat->id]);
        Livewire::test(ProductsManage::class)->call('editProduct', $p->id)->assertSee('Prendre une photo')->assertSee('Choisir dans le téléphone');
    }

    public function test_un_nouveau_produit_peut_avoir_sa_photo_des_la_creation(): void
    {
        $this->actingAs($this->patron);
        $cat = Category::factory()->create(['restaurant_id' => $this->maquis->id, 'kind' => 'drink', 'name' => 'Bières']);

        $screen = Livewire::test(ProductsManage::class)->call('openCreateForm');
        $this->assertNull($screen->call('stageProductPhoto', $this->photo())->get('effects')['returns'][0] ?? null);
        $pending = $screen->get('pendingPhoto');
        Storage::disk('local')->assertExists($pending);
        $screen->assertSee("posée à l'enregistrement");

        $screen->set('name', 'Flag')->set('sellingPrice', '700')->set('costPrice', '450')
            ->set('categoryId', $cat->id)->set('stockQuantity', '24')
            ->call('saveProduct')
            ->assertHasNoErrors()
            ->assertSet('pendingPhoto', null);

        $product = Product::where('name', 'Flag')->sole();
        $this->assertSame($pending, $product->photo);
        Storage::disk('local')->assertExists($product->photo);
    }

    public function test_la_photo_d_un_produit_abandonne_est_jetee(): void
    {
        $this->actingAs($this->patron);

        $screen = Livewire::test(ProductsManage::class)->call('openCreateForm')->call('stageProductPhoto', $this->photo());
        $pending = $screen->get('pendingPhoto');
        $screen->set('showProductForm', false);
        Storage::disk('local')->assertMissing($pending);
        $this->assertNull($screen->get('pendingPhoto'));

        // Retirée avant d'enregistrer
        $screen->call('openCreateForm')->call('stageProductPhoto', $this->photo());
        $pending = $screen->get('pendingPhoto');
        $screen->call('discardPendingPhoto');
        Storage::disk('local')->assertMissing($pending);
    }

    public function test_on_ne_peut_pas_glisser_le_chemin_d_une_autre_photo(): void
    {
        $this->actingAs($this->patron);
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        Livewire::test(ProductsManage::class)->call('openCreateForm')->set('pendingPhoto', 'recus/1/autre.jpg');
    }
}
