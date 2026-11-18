<?php

namespace Tests\Feature;

use App\Livewire\CashRegisterClosed;
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
use App\Support\RegisterReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Le rapport du soir : ce que le patron reçoit sur WhatsApp à la clôture.
 */
class RegisterReportTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;
    private User $aya;
    private User $awa;
    private Product $biere;
    private Product $poulet;
    private CashRegister $caisse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maquis = Restaurant::factory()->create(['name' => 'Maquis Chez Koffi', 'credit_enabled' => true]);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'phone' => '0711111111']);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);
        $this->aya = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Aya']);
        $this->awa = User::factory()->server()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Awa']);
        $this->biere = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Bière', 'selling_price' => 1000]);
        $this->poulet = Product::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'Poulet', 'selling_price' => 3500]);

        $this->caisse = CashRegister::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'opened_by' => $this->jean->id,
            'opening_amount' => 10000,
            'opened_at' => now()->subHours(5),
        ]);
    }

    /** Un bon envoyé au comptoir par $serveuse. */
    private function bon(User $serveuse, Product $product, int $qty): Order
    {
        $order = Order::factory()->awaitingPayment($product->selling_price * $qty)->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $serveuse->id,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $product->selling_price,
            'total' => $product->selling_price * $qty,
        ]);

        return $order;
    }

    /** Une soirée : 3 bons encaissés, 1 sur l'ardoise, 1 annulé, 1 dépense. */
    private function soiree(): void
    {
        Payment::recordCashPayment($this->bon($this->aya, $this->biere, 5), $this->jean, 5000);
        Payment::recordCashPayment($this->bon($this->aya, $this->poulet, 2), $this->jean, 10000);
        Payment::recordCashPayment($this->bon($this->awa, $this->biere, 3), $this->jean, 3000);

        $konan = Customer::factory()->create(['restaurant_id' => $this->maquis->id, 'name' => 'M. Konan']);
        CreditTransaction::chargeOrder($this->bon($this->awa, $this->biere, 4), $konan, $this->patron);

        $this->bon($this->aya, $this->biere, 2)->cancel();

        Expense::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'cash_register_id' => $this->caisse->id,
            'amount' => 2500,
        ]);

        // Attendu : 10 000 + 15 000 encaissés - 2 500 = 22 500 ; on compte 22 000
        $this->caisse->close(22000, $this->jean->id, 'Billet abîmé');
    }

    public function test_le_rapport_compte_toutes_les_ventes_de_la_session(): void
    {
        $this->soiree();
        $report = new RegisterReport($this->caisse->fresh());

        $this->assertSame(15000, $report->cashSales);
        $this->assertSame(3, $report->cashSalesCount);
        $this->assertSame(4000, $report->creditSales);
        $this->assertSame(19000, $report->totalSales());
        $this->assertSame(4, $report->totalBons());
        $this->assertSame(1, $report->cancelledCount);
        $this->assertSame(2000, $report->cancelledTotal);
        $this->assertSame(4000, $report->ardoiseDue);
    }

    public function test_le_rapport_detaille_chaque_serveuse_et_les_meilleures_ventes(): void
    {
        $this->soiree();
        $report = new RegisterReport($this->caisse->fresh());

        $aya = $report->byServer->firstWhere('name', 'Aya');
        $awa = $report->byServer->firstWhere('name', 'Awa');
        $this->assertSame([2, 12000], [$aya->bons, $aya->amount]);
        // Awa : un bon encaissé + un bon sur l'ardoise
        $this->assertSame([2, 7000], [$awa->bons, $awa->amount]);

        $top = $report->topProducts->first();
        $this->assertSame(['Bière', 12], [$top->name, $top->quantity]);
    }

    public function test_le_texte_whatsapp_contient_l_essentiel(): void
    {
        $this->soiree();
        $text = (new RegisterReport($this->caisse->fresh()))->toWhatsappText();

        $this->assertStringContainsString('*Maquis Chez Koffi*', $text);
        $this->assertStringContainsString('*Ventes : 19 000 FCFA* (4 bons)', $text);
        $this->assertStringContainsString("• Sur l'ardoise : 4 000", $text);
        $this->assertStringContainsString('• Dépenses : 2 500', $text);
        $this->assertStringContainsString('• Écart : -500 ⚠️', $text);
        $this->assertStringContainsString('• Note : Billet abîmé', $text);
        $this->assertStringContainsString('• Aya : 2 bons · 12 000', $text);
        $this->assertStringContainsString('Bière ×12', $text);
        $this->assertStringContainsString('• Bons annulés : 1 (2 000 FCFA)', $text);
        $this->assertStringContainsString('• Ardoise totale due : 4 000 FCFA', $text);
    }

    public function test_une_session_d_un_autre_jour_n_est_pas_melangee(): void
    {
        // Vente faite avant l'ouverture de la caisse : hors session
        $avant = $this->bon($this->aya, $this->biere, 1);
        Payment::recordCashPayment($avant, $this->jean, 1000);
        Payment::where('order_id', $avant->id)->update(['paid_at' => now()->subHours(8)]);

        $this->caisse->close(10000, $this->jean->id);
        $report = new RegisterReport($this->caisse->fresh());

        $this->assertSame(0, $report->totalSales());
        $this->assertStringContainsString('• Écart : 0 ✅', $report->toWhatsappText());
    }

    public function test_le_lien_whatsapp_part_vers_le_numero_du_patron(): void
    {
        $this->soiree();
        $report = new RegisterReport($this->caisse->fresh());

        $this->assertStringStartsWith('https://wa.me/2250711111111?text=', $report->whatsappToOwnerUrl());
        $this->assertStringContainsString(rawurlencode('*Ventes : 19 000 FCFA*'), $report->whatsappToOwnerUrl());
    }

    public function test_la_caissiere_voit_le_rapport_et_peut_l_envoyer_au_patron(): void
    {
        $this->soiree();
        $this->actingAs($this->jean);

        Livewire::test(CashRegisterClosed::class, ['register' => $this->caisse->id])
            ->assertSee('Rapport du soir')
            ->assertSee('19 000 FCFA')
            ->assertSee('Envoyer au patron sur WhatsApp')
            ->assertSee('https://wa.me/2250711111111', false);
    }

    public function test_le_patron_n_a_pas_besoin_de_s_envoyer_le_rapport(): void
    {
        $this->soiree();
        $this->actingAs($this->patron);

        Livewire::test(CashRegisterClosed::class, ['register' => $this->caisse->id])
            ->assertDontSee('Envoyer au patron sur WhatsApp')
            ->assertSee('Partager sur WhatsApp');
    }

    public function test_l_historique_donne_acces_aux_rapports(): void
    {
        $this->soiree();

        $this->actingAs($this->patron)
            ->get(route('cash-register.history'))
            ->assertOk()
            ->assertSee(route('cash-register.closed', $this->caisse->id), false);
    }
}
