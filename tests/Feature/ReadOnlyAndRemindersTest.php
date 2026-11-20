<?php

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Livewire\Admin\RestaurantsList;
use App\Livewire\Ardoise;
use App\Livewire\ExpensesList;
use App\Livewire\StockManage;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\SubscriptionReminder;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Maquis impayé : lecture seule pour le patron et la gérante.
 * Rappels d'échéance envoyés au patron (J-7, J-3, J-1, J0, retard, coupure).
 */
class ReadOnlyAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $mariam;
    private User $jean;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(9, 0));

        $this->maquis = Restaurant::factory()->create(['name' => 'Maquis Chez Koffi', 'credit_enabled' => true]);
        $this->maquis->forceFill([
            'plan_id' => Plan::where('code', 'pro')->value('id'),
            'paid_until' => now()->addDays(3)->endOfDay(), // échéance le 21/11
        ])->save();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id, 'phone' => '0711111111']);
        $this->mariam = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id]);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
    }

    private function verrouiller(): void
    {
        $this->maquis->update(['subscription_status' => 'suspended']);
    }

    // ================= Lecture seule =================

    public function test_le_patron_consulte_ses_chiffres_en_lecture_seule(): void
    {
        $this->verrouiller();
        $this->actingAs($this->patron);

        foreach (['dashboard.index', 'sales.today', 'cash-register.history', 'expenses.index', 'stock.index', 'exports.index', 'settings'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('dashboard.index'))
            ->assertSee('Mode lecture seule')
            ->assertSee("Payer l'abonnement", false)
            // Plus de menus pour vendre
            ->assertDontSee('Tables &amp; commandes', false)
            ->assertDontSee('href="' . route('cash-register.manage') . '" class="sidebar-link', false)
            ->assertDontSee('Menu &amp; produits', false);
    }

    public function test_les_ecrans_pour_vendre_renvoient_au_tableau_de_bord(): void
    {
        $this->verrouiller();

        foreach (['tables.index', 'orders.new', 'counter', 'cash-register.manage', 'products.manage'] as $route) {
            $this->actingAs($this->mariam)
                ->get(route($route))
                ->assertRedirect(route('dashboard.index'));
        }
    }

    public function test_aucune_ecriture_n_est_possible_meme_en_contournant_l_ecran(): void
    {
        $order = Order::factory()->awaitingPayment(3000)->create(['restaurant_id' => $this->maquis->id]);
        $this->openRegister($this->maquis, $this->patron);
        $this->verrouiller();
        $this->actingAs($this->patron);

        $tentatives = [
            'vente' => fn () => Payment::recordCashPayment($order, $this->patron, 3000),
            'dépense' => fn () => Expense::create(['category' => 'other', 'amount' => 1000, 'user_id' => $this->patron->id, 'expense_date' => now()]),
            'client' => fn () => Customer::create(['name' => 'Nouveau']),
            'caisse' => fn () => CashRegister::create(['opened_by' => $this->patron->id, 'opening_amount' => 0, 'opened_at' => now()]),
            'produit' => fn () => Product::factory()->create(['restaurant_id' => $this->maquis->id]),
        ];

        foreach ($tentatives as $quoi => $tentative) {
            try {
                $tentative();
                $this->fail("Écriture acceptée en lecture seule : $quoi.");
            } catch (BusinessRuleException $e) {
                $this->assertStringContainsString('lecture seule', $e->getMessage());
            }
        }

        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Expense::count());
    }

    public function test_l_ecran_affiche_le_message_au_lieu_de_planter(): void
    {
        $this->verrouiller();
        $this->actingAs($this->patron);

        Livewire::test(ExpensesList::class)
            ->assertSee('Lecture seule')
            ->set('amount', 1000)
            ->call('addExpense')
            ->assertHasErrors('business');

        $this->assertSame(0, Expense::count());
    }

    public function test_les_formulaires_d_enregistrement_sont_caches(): void
    {
        $this->verrouiller();
        $this->actingAs($this->patron);

        Livewire::test(StockManage::class)->assertDontSee('Livraison')->assertDontSee('Inventaire');
        Livewire::test(Ardoise::class)->assertDontSee('+ Nouveau client');
    }

    public function test_la_serveuse_et_la_caissiere_restent_bloquees(): void
    {
        $this->verrouiller();

        $this->actingAs($this->jean)->get(route('dashboard.index'))->assertRedirect(route('account.locked'));
    }

    public function test_le_super_admin_garde_tous_les_droits(): void
    {
        $this->verrouiller();
        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->actingAs($admin);
        session([User::ADMIN_RESTAURANT_SESSION_KEY => $this->maquis->id]);

        Customer::create(['name' => 'Dépannage']);
        $this->assertSame(1, Customer::count());
    }

    public function test_apres_paiement_tout_revient(): void
    {
        $this->verrouiller();
        $this->maquis->update(['subscription_status' => 'active']);
        $this->maquis->forceFill(['paid_until' => now()->addMonth()])->save();
        $this->actingAs($this->patron->fresh());

        $this->get(route('tables.index'))->assertOk();
        Customer::create(['name' => 'Nouveau']);
        $this->assertSame(1, Customer::count());
    }

    // ================= Rappels d'échéance =================

    private function configurerWhatsApp(): void
    {
        config([
            'services.whatsapp.token' => 'jeton',
            'services.whatsapp.phone_number_id' => '123',
            'maquis.billing.wave' => '0700000000',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);
    }

    public function test_rappel_envoye_a_j_moins_3_au_patron(): void
    {
        $this->configurerWhatsApp();

        $this->artisan('subscriptions:remind')->assertSuccessful();

        $reminder = SubscriptionReminder::sole();
        $this->assertSame(['J-3', 'sent', '0711111111'], [$reminder->kind, $reminder->status, $reminder->phone]);

        Http::assertSent(function (Request $r) {
            $params = collect($r->data()['template']['components'][0]['parameters'])->pluck('text');

            return $r->data()['to'] === '2250711111111'
                && $r->data()['template']['name'] === 'rappel_abonnement'
                && $params[0] === 'Maquis Chez Koffi'
                && $params[1] === 'Votre abonnement se termine le 21/11/2026, dans 3 jours'
                && $params[2] === 'Pro : 10 000 FCFA/mois'
                && $params[3] === $this->maquis->paymentReference()
                && $params[4] === 'Wave 0700000000';
        });
    }

    public function test_un_rappel_n_est_jamais_envoye_deux_fois(): void
    {
        $this->configurerWhatsApp();

        $this->artisan('subscriptions:remind');
        $this->artisan('subscriptions:remind');

        Http::assertSentCount(1);
        $this->assertSame(1, SubscriptionReminder::count());
    }

    public function test_les_etapes_du_rappel(): void
    {
        $etapes = [
            '2026-11-14 09:00' => 'J-7',
            '2026-11-15 09:00' => null,
            '2026-11-20 09:00' => 'J-1',
            '2026-11-21 09:00' => 'J0',
            '2026-11-23 09:00' => 'retard',
            '2026-11-25 10:30' => 'coupe',
        ];

        foreach ($etapes as $quand => $attendu) {
            $this->travelTo(\Carbon\Carbon::parse($quand));
            $this->assertSame($attendu, $this->maquis->fresh()->reminderKind(), "Le $quand");
        }
    }

    public function test_les_messages_de_retard_et_de_coupure(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-23 09:00'));
        $this->assertSame(
            "Votre abonnement est terminé depuis le 21/11/2026 : l'accès sera coupé le 25/11/2026 à 10 h",
            $this->maquis->fresh()->reminderSituation(),
        );

        $this->travelTo(\Carbon\Carbon::parse('2026-11-25 11:00'));
        $this->assertStringContainsString('lecture seule', $this->maquis->fresh()->reminderSituation());
    }

    public function test_l_essai_qui_se_termine_est_aussi_rappele(): void
    {
        $this->maquis->update(['subscription_status' => 'trial', 'trial_ends_at' => now()->addDay()->endOfDay()]);

        $this->assertSame('J-1', $this->maquis->fresh()->reminderKind());
        $this->assertStringStartsWith('Votre essai gratuit se termine', $this->maquis->fresh()->reminderSituation());
    }

    public function test_une_suspension_manuelle_n_envoie_pas_de_rappel(): void
    {
        $this->verrouiller();

        $this->assertNull($this->maquis->fresh()->reminderKind());
    }

    public function test_sans_api_whatsapp_le_super_admin_envoie_le_rappel_d_un_clic(): void
    {
        Http::fake();

        $this->artisan('subscriptions:remind');

        Http::assertNothingSent();
        $this->assertSame('manual', SubscriptionReminder::sole()->status);

        $admin = User::factory()->create(['restaurant_id' => null]);
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->actingAs($admin);

        Livewire::test(RestaurantsList::class)
            ->assertSee('Rappels du jour à envoyer à la main (1)')
            ->assertSee('https://wa.me/2250711111111?text=', false)
            ->assertSee('Relancer');
    }

    public function test_le_rappel_est_planifie_chaque_matin(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());

        $event = $events->first(fn ($e) => str_contains($e->command ?? '', 'subscriptions:remind'));

        $this->assertNotNull($event);
        $this->assertSame('0 9 * * *', $event->expression);
    }
}
