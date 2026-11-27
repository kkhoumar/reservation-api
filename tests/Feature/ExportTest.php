<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\CsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exports CSV (ouvrables dans Excel) pour le patron, la gérante et le comptable.
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(20, 0));

        $this->maquis = Restaurant::factory()->create(['name' => 'Maquis Chez Koffi', 'credit_enabled' => true]);

        $this->openRegister($this->maquis);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
    }

    private function bon(int $total): Order
    {
        $product = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Bière', 'selling_price' => 1000, 'cost_price' => 600]);
        $order = Order::factory()->awaitingPayment($total)->create(['restaurant_id' => $this->maquis->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'quantity' => $total / 1000, 'unit_price' => 1000, 'unit_cost' => 600, 'total' => $total,
        ]);

        return $order;
    }

    private function csv(string $type, array $query = []): string
    {
        $response = $this->actingAs($this->patron)->get(route('exports.download', ['type' => $type, 'format' => 'csv'] + $query));
        $response->assertOk();

        return $response->streamedContent();
    }

    public function test_la_page_exports_est_reservee_au_patron_et_a_la_gerante(): void
    {
        $this->actingAs($this->patron)->get(route('exports.index'))->assertOk()->assertSee('Tout télécharger (Excel)');

        $this->actingAs($this->jean)->get(route('exports.index'))->assertRedirect(route('tables.index'));
        $this->actingAs($this->jean)->get(route('exports.download', 'ventes'))->assertRedirect(route('tables.index'));
    }

    public function test_le_fichier_s_ouvre_dans_excel_en_francais(): void
    {
        $response = $this->actingAs($this->patron)->get(route('exports.download', ['type' => 'depenses', 'format' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('maquis-chez-koffi-depenses-2026-11-01-au-2026-11-18.csv', $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);           // accents corrects
        $this->assertStringContainsString('Date;Heure;Catégorie', $content); // séparateur « ; »
    }

    public function test_export_des_ventes_tous_moyens_de_paiement(): void
    {
        Payment::recordCashPayment($this->bon(3000), $this->jean, 5000);
        Payment::recordPayment($this->bon(2000), $this->jean, 0, 'wave', 2000, 'TX-1');
        $konan = Customer::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'M. Konan']);
        CreditTransaction::chargeOrder($this->bon(4000), $konan, $this->patron);

        $csv = $this->csv('ventes');

        $this->assertStringContainsString(';Espèces;;3000;0;3000;3000;0;0;', $csv);
        $this->assertStringContainsString(';Wave;TX-1;2000;0;2000;0;2000;0;', $csv);
        $this->assertStringContainsString(';Crédit;;4000;0;4000;0;0;4000;"M. Konan"', $csv);
    }

    public function test_export_des_articles_avec_marge(): void
    {
        Payment::recordCashPayment($this->bon(3000), $this->jean, 3000);

        $csv = $this->csv('articles');

        // 3 bières à 1 000, achat 600 : marge 1 200
        $this->assertStringContainsString(';Bière;3;1000;3000;600;1200;Non;', $csv);
    }

    public function test_seule_la_periode_choisie_est_exportee(): void
    {
        Expense::factory()->create(['restaurant_id' => $this->maquis->id, 'description' => 'Glace novembre', 'amount' => 1500, 'expense_date' => now()]);
        Expense::factory()->create(['restaurant_id' => $this->maquis->id, 'description' => 'Loyer octobre', 'amount' => 90000, 'expense_date' => now()->setDate(2026, 10, 5)]);

        $novembre = $this->csv('depenses');
        $this->assertStringContainsString('Glace novembre', $novembre);
        $this->assertStringNotContainsString('Loyer octobre', $novembre);

        $octobre = $this->csv('depenses', ['from' => '2026-10-01', 'to' => '2026-10-31']);
        $this->assertStringContainsString('Loyer octobre', $octobre);
    }

    public function test_les_donnees_d_un_autre_maquis_ne_sortent_jamais(): void
    {
        $autre = Restaurant::factory()->create();
        Expense::factory()->create(['restaurant_id' => $autre->id, 'description' => 'Dépense du voisin', 'expense_date' => now()]);
        Customer::factory()->create(['restaurant_id' => $autre->id, 'name' => 'Client du voisin']);

        $this->assertStringNotContainsString('Dépense du voisin', $this->csv('depenses'));
        $this->assertStringNotContainsString('Client du voisin', $this->csv('credits'));
    }

    public function test_protection_contre_les_formules_cachees_dans_un_nom(): void
    {
        Customer::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => '=HYPERLINK("http://pirate")']);

        $csv = $this->csv('credits');

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(';"=HYPERLINK', $csv);
        $this->assertSame("'+33", CsvExport::cell('+33'));
        $this->assertSame(-500, CsvExport::cell(-500)); // les nombres restent des nombres
    }

    public function test_export_du_stock_et_des_caisses(): void
    {
        $this->bon(1000);

        $this->assertStringContainsString('ÉTAT DU STOCK', $this->csv('stock'));
        $this->assertStringContainsString('Ouverture;Clôture', $this->csv('caisses'));
    }

    public function test_un_export_inconnu_n_existe_pas(): void
    {
        $this->actingAs($this->patron)->get(route('exports.download', 'salaires'))->assertNotFound();
    }
}
