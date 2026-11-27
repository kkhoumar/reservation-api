<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\XlsxWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/**
 * Exports en vrai fichier Excel : un fichier qui s'ouvre partout (téléphone
 * compris), un onglet par sujet, et un Résumé identique au tableau de bord.
 */
class ExcelExportTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;
    private CashRegister $caisse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(20, 0));

        $this->maquis = Restaurant::factory()->create(['name' => 'Maquis Chez Koffi', 'credit_enabled' => true]);
        $this->openRegister($this->maquis);
        $this->caisse = CashRegister::sole();
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

    /** Télécharge un export Excel et renvoie [nom d'onglet => contenu XML]. */
    private function xlsx(string $type, array $query = []): array
    {
        $response = $this->actingAs($this->patron)->get(route('exports.download', ['type' => $type] + $query));
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        preg_match_all('/<sheet name="([^"]+)"/', $zip->getFromName('xl/workbook.xml'), $m);

        $sheets = [];
        foreach ($m[1] as $i => $name) {
            $xml = $zip->getFromName('xl/worksheets/sheet' . ($i + 1) . '.xml');
            $this->assertNotFalse(simplexml_load_string($xml), "Onglet « {$name} » mal formé");
            $sheets[html_entity_decode($name)] = $xml;
        }
        $zip->close();

        return $sheets;
    }

    /** Valeur (colonne B) d'une ligne du Résumé, trouvée par son libellé. */
    private function summaryValue(string $xml, string $label): ?int
    {
        $quoted = preg_quote(htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8'), '/');

        return preg_match('/<t xml:space="preserve">' . $quoted . '<\/t><\/is><\/c><c r="B\d+" s="\d+"><v>(-?\d+)<\/v>/', $xml, $m) ? (int) $m[1] : null;
    }

    private function activite(): void
    {
        Payment::recordCashPayment($this->bon(3000), $this->jean, 5000);
        Payment::recordPayment($this->bon(2000), $this->jean, 0, 'wave', 2000, 'TX-1');
        $konan = Customer::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'M. Konan']);
        CreditTransaction::chargeOrder($this->bon(4000), $konan, $this->patron);

        Expense::factory()->create(['restaurant_id' => $this->maquis->id, 'category' => 'other', 'description' => 'Gaz', 'amount' => 1500, 'expense_date' => now(),
            'receipt_path' => 'recus/x.jpg', 'receipt_source' => 'gallery', 'receipt_taken_at' => now()->subDays(9)]);
        Expense::factory()->create(['restaurant_id' => $this->maquis->id, 'category' => 'goods_purchase', 'description' => 'Casier', 'amount' => 5000, 'expense_date' => now()]);

        CashMovement::create(['restaurant_id' => $this->maquis->id, 'cash_register_id' => $this->caisse->id, 'user_id' => $this->patron->id, 'type' => 'in', 'amount' => 2000, 'reason' => 'forgotten']);
        CashMovement::create(['restaurant_id' => $this->maquis->id, 'cash_register_id' => $this->caisse->id, 'user_id' => $this->patron->id, 'type' => 'out', 'amount' => 10000, 'reason' => 'owner_took', 'note' => 'Gaz du soir']);
    }

    public function test_un_vrai_fichier_excel_par_defaut(): void
    {
        $this->activite();
        $sheets = $this->xlsx('ventes');

        $this->assertSame(['Ventes'], array_keys($sheets));
        $this->assertStringContainsString('M. Konan', $sheets['Ventes']);
        $this->assertStringContainsString('<pane ySplit="1"', $sheets['Ventes']); // en-têtes figés
        $this->assertStringContainsString('<autoFilter', $sheets['Ventes']);      // filtres
        $this->assertStringContainsString('<v>3000</v>', $sheets['Ventes']);      // les montants sont des nombres
    }

    public function test_tout_en_un_fichier_avec_un_onglet_par_sujet(): void
    {
        $this->activite();
        $sheets = $this->xlsx('tout');

        $this->assertSame([
            'Résumé', 'Ventes', 'Articles vendus', 'Dépenses', 'Clôtures de caisse', 'Argent du tiroir',
            'Crédits - soldes', 'Crédits - mouvements', 'État du stock', 'Stock - mouvements',
        ], array_keys($sheets));
    }

    public function test_le_resume_donne_les_memes_chiffres_que_le_tableau_de_bord(): void
    {
        $this->activite();
        $resume = $this->xlsx('tout')['Résumé'];

        $board = Livewire::actingAs($this->patron)->test(Dashboard::class)->call('setPeriod', 'month');

        $this->assertSame(9000, $this->summaryValue($resume, 'Chiffre d\'affaires (crédit compris)'));
        $this->assertSame($board->viewData('revenue'), $this->summaryValue($resume, 'Chiffre d\'affaires (crédit compris)'));
        $this->assertSame($board->viewData('profit'), $this->summaryValue($resume, '= Bénéfice estimé'));
        $this->assertSame($board->viewData('expenses'), $this->summaryValue($resume, 'Total des dépenses'));
        // 9 000 − 5 400 (prix d'achat) − 1 500 (gaz) = 2 100
        $this->assertSame(2100, $this->summaryValue($resume, '= Bénéfice estimé'));
        $this->assertSame(2000, $this->summaryValue($resume, 'Argent ajouté dans le tiroir'));
        $this->assertSame(10000, $this->summaryValue($resume, 'Argent retiré du tiroir'));
        $this->assertSame(4000, $this->summaryValue($resume, 'Crédits que les clients doivent encore'));
    }

    public function test_argent_du_tiroir_et_reçu_des_depenses_sont_exportes(): void
    {
        $this->activite();
        $sheets = $this->xlsx('tout');

        $this->assertStringContainsString('Retrait', $sheets['Argent du tiroir']);
        $this->assertStringContainsString('Gaz du soir', $sheets['Argent du tiroir']);
        $this->assertStringContainsString('<v>-10000</v>', $sheets['Argent du tiroir']);
        $this->assertStringContainsString('À vérifier : photo ancienne (photo du 09/11/2026)', $sheets['Dépenses']);
        $this->assertStringContainsString('Argent retiré', $sheets['Clôtures de caisse']);
    }

    public function test_les_articles_sont_dates_comme_les_ventes(): void
    {
        // Bon payé en octobre, retouché en novembre : il reste en octobre
        $this->travelTo(now()->setDate(2026, 10, 20));
        $order = $this->bon(3000);
        Payment::recordCashPayment($order, $this->jean, 3000);
        $this->travelTo(now()->setDate(2026, 11, 18));
        $order->touch();

        $novembre = $this->actingAs($this->patron)->get(route('exports.download', ['type' => 'articles', 'format' => 'csv']))->streamedContent();
        $this->assertStringNotContainsString('Bière', $novembre);

        $octobre = $this->actingAs($this->patron)->get(route('exports.download', ['type' => 'articles', 'format' => 'csv', 'from' => '2026-10-01', 'to' => '2026-10-31']))->streamedContent();
        $this->assertStringContainsString('20/10/2026', $octobre);
        $this->assertStringContainsString(';Bière;3;1000;3000;600;1200;Non;', $octobre);
    }

    public function test_un_autre_maquis_ne_sort_jamais_dans_le_fichier_complet(): void
    {
        $autre = Restaurant::factory()->create();
        Expense::factory()->create(['restaurant_id' => $autre->id, 'description' => 'Dépense du voisin', 'expense_date' => now()]);
        Customer::factory()->create(['restaurant_id' => $autre->id, 'name' => 'Client du voisin']);

        $all = implode('', $this->xlsx('tout'));
        $this->assertStringNotContainsString('Dépense du voisin', $all);
        $this->assertStringNotContainsString('Client du voisin', $all);
    }

    public function test_un_texte_piege_reste_du_texte_dans_excel(): void
    {
        Customer::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => '=HYPERLINK("http://pirate") <b>&']);
        $soldes = $this->xlsx('credits')['Crédits - soldes'];

        $this->assertStringContainsString('t="inlineStr"', $soldes);
        $this->assertStringNotContainsString('<f>', $soldes); // jamais une formule
        $this->assertStringContainsString('=HYPERLINK(&quot;http://pirate&quot;) &lt;b&gt;&amp;', $soldes);
    }

    public function test_les_colonnes_excel_sont_bien_nommees(): void
    {
        $this->assertSame('A', XlsxWriter::col(0));
        $this->assertSame('Z', XlsxWriter::col(25));
        $this->assertSame('AA', XlsxWriter::col(26));
        $this->assertSame('AZ', XlsxWriter::col(51));
    }

    public function test_les_serveuses_ne_telechargent_pas(): void
    {
        $this->actingAs($this->jean)->get(route('exports.download', 'tout'))->assertRedirect(route('tables.index'));
    }
}
