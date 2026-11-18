<?php

namespace Tests\Feature;

use App\Actions\RecordSubscriptionPayment;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Admin\PlansManage;
use App\Livewire\Admin\RestaurantSubscription;
use App\Livewire\Admin\RestaurantsList;
use App\Livewire\UsersManage;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Abonnements des maquis : formules, paiements enregistrés par le Super Admin,
 * échéance, jours de grâce, verrouillage automatique, limite d'utilisateurs.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Restaurant $maquis;
    private User $patron;
    private Plan $basic;
    private Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(10, 0));

        $this->admin = User::factory()->create(['restaurant_id' => null, 'role' => 'owner']);
        $this->admin->forceFill(['is_platform_admin' => true])->save();

        $this->basic = Plan::where('code', 'basic')->sole();
        $this->pro = Plan::where('code', 'pro')->sole();

        $this->maquis = Restaurant::factory()->create([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(10),
        ]);
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
    }

    private function payer(Plan $plan, int $months, ?Restaurant $restaurant = null): SubscriptionPayment
    {
        return app(RecordSubscriptionPayment::class)->handle($restaurant ?? $this->maquis, $plan, $months, 'wave', $this->admin);
    }

    public function test_les_trois_formules_existent_avec_leurs_limites(): void
    {
        $this->assertSame([5000, 3], [$this->basic->monthly_price, $this->basic->max_users]);
        $this->assertSame([10000, 8], [$this->pro->monthly_price, $this->pro->max_users]);
        $this->assertNull(Plan::where('code', 'premium')->sole()->max_users);
    }

    public function test_un_paiement_pendant_l_essai_active_le_maquis_a_partir_d_aujourd_hui(): void
    {
        $payment = $this->payer($this->pro, 3);

        $maquis = $this->maquis->fresh();
        $this->assertSame('active', $maquis->subscription_status);
        $this->assertSame($this->pro->id, $maquis->plan_id);
        $this->assertSame('2027-02-18', $maquis->paid_until->toDateString());
        $this->assertSame(30000, $payment->amount);
        $this->assertSame($this->admin->id, $payment->recorded_by);
    }

    public function test_un_renouvellement_prolonge_sans_perdre_les_jours_deja_payes(): void
    {
        $this->payer($this->basic, 1);  // jusqu'au 18/12
        $this->travelTo(now()->addDays(20)); // 8 décembre, encore 10 jours payés
        $this->payer($this->basic, 1);

        $this->assertSame('2027-01-18', $this->maquis->fresh()->paid_until->toDateString());
    }

    public function test_apres_l_echeance_3_jours_de_grace_puis_verrouillage(): void
    {
        $this->payer($this->basic, 1); // payé jusqu'au 18/12

        $this->travelTo(now()->setDate(2026, 12, 20)->setTime(10, 0));
        $maquis = $this->maquis->fresh();
        $this->assertTrue($maquis->isInGracePeriod());
        $this->assertFalse($maquis->isLocked());
        $this->assertSame('En retard', $maquis->subscriptionBadge()[0]);

        // Jamais coupé en plein service : le 21 au soir et le 22 avant 10 h, ça marche encore
        foreach ([[21, 23], [22, 9]] as [$day, $hour]) {
            $this->travelTo(now()->setDate(2026, 12, $day)->setTime($hour, 30));
            $this->assertFalse($this->maquis->fresh()->isLocked(), "Coupé le $day à {$hour} h 30");
        }

        $this->travelTo(now()->setDate(2026, 12, 22)->setTime(10, 1));
        $maquis = $this->maquis->fresh();
        $this->assertSame('2026-12-22 10:00', $maquis->lockAt()->format('Y-m-d H:i'));
        $this->assertTrue($maquis->isSubscriptionExpired());
        $this->assertTrue($maquis->isLocked());

        // Le patron passe en lecture seule : plus de vente
        $this->actingAs($this->patron)
            ->get(route('tables.index'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_payer_apres_un_impaye_debloque_et_repart_d_aujourd_hui(): void
    {
        $this->payer($this->basic, 1);
        $this->travelTo(now()->setDate(2027, 1, 10)->setTime(10, 0));
        $this->assertTrue($this->maquis->fresh()->isLocked());

        $this->payer($this->basic, 1);

        $maquis = $this->maquis->fresh();
        $this->assertFalse($maquis->isLocked());
        $this->assertSame('2027-02-10', $maquis->paid_until->toDateString());
    }

    public function test_seul_le_super_admin_enregistre_un_paiement(): void
    {
        $this->expectException(BusinessRuleException::class);
        app(RecordSubscriptionPayment::class)->handle($this->maquis, $this->pro, 1, 'wave', $this->patron);
    }

    public function test_duree_et_moyen_de_paiement_controles(): void
    {
        foreach ([[2, 'wave'], [1, 'bitcoin']] as [$months, $method]) {
            try {
                app(RecordSubscriptionPayment::class)->handle($this->maquis, $this->pro, $months, $method, $this->admin);
                $this->fail("Accepté : $months mois par $method.");
            } catch (BusinessRuleException) {
            }
        }

        $this->assertSame(0, SubscriptionPayment::count());
    }

    public function test_le_patron_n_a_pas_acces_aux_ecrans_d_abonnement_du_super_admin(): void
    {
        $this->actingAs($this->patron)
            ->get(route('admin.restaurants.subscription', $this->maquis->id))
            ->assertRedirect(route('tables.index'));

        $this->actingAs($this->patron)
            ->get(route('admin.plans'))
            ->assertRedirect(route('tables.index'));
    }

    public function test_ecran_super_admin_enregistrer_un_paiement(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(RestaurantSubscription::class, ['restaurant' => $this->maquis])
            ->set('planId', $this->pro->id)
            ->set('months', 6)
            ->assertSet('amount', 60000)
            ->set('amount', 55000) // remise
            ->set('method', 'orange_money')
            ->set('reference', 'OM-778')
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSee('55 000 FCFA enregistré')
            ->assertSee('OM-778');

        $this->assertSame(55000, SubscriptionPayment::sole()->amount);
        $this->assertSame('2027-05-18', $this->maquis->fresh()->paid_until->toDateString());
    }

    public function test_le_super_admin_modifie_le_prix_d_une_formule(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(PlansManage::class)
            ->set("form.{$this->basic->id}.monthly_price", 7000)
            ->call('save', $this->basic->id)
            ->assertHasNoErrors();

        // Le prochain paiement utilise le nouveau prix
        $this->assertSame(7000, $this->payer($this->basic->fresh(), 1)->amount);
    }

    public function test_la_liste_des_maquis_montre_le_revenu_et_les_maquis_a_relancer(): void
    {
        $this->payer($this->pro, 1);
        $enRetard = Restaurant::factory()->create();
        $this->payer($this->basic, 1, $enRetard);
        $enRetard->forceFill(['paid_until' => now()->subDay()])->save();

        $this->actingAs($this->admin);

        Livewire::test(RestaurantsList::class)
            ->assertViewHas('monthlyRevenue', 15000)
            ->assertViewHas('toChaseCount', 1)
            ->assertSee('En retard')
            ->assertSee($this->maquis->paymentReference());
    }

    public function test_la_formule_limite_le_nombre_d_utilisateurs_actifs(): void
    {
        $this->payer($this->basic, 1); // 3 utilisateurs
        $mariam = User::factory()->manager()->create(['restaurant_id' => $this->maquis->id]);
        User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id]);
        $this->actingAs($this->patron);

        $nouvelle = fn () => Livewire::test(UsersManage::class)
            ->call('openCreateForm')
            ->set('firstName', 'Aya')
            ->set('lastName', 'Konan')
            ->set('phone', '0722222222')
            ->set('password', 'motdepasse')
            ->set('role', 'server')
            ->call('save');

        $nouvelle()->assertHasErrors('limit');
        $this->assertSame(3, User::where('restaurant_id', $this->maquis->id)->count());

        // Désactiver quelqu'un libère une place
        Livewire::test(UsersManage::class)->call('toggleStatus', $mariam->id);
        $nouvelle()->assertHasNoErrors();
        $this->assertSame(4, User::where('restaurant_id', $this->maquis->id)->count());

        // … mais on ne peut plus réactiver Mariam sans dépasser
        Livewire::test(UsersManage::class)->call('toggleStatus', $mariam->id)->assertHasErrors('limit');
        $this->assertSame('inactive', $mariam->fresh()->status);
    }

    public function test_le_patron_voit_son_abonnement_et_comment_payer(): void
    {
        config(['maquis.billing.wave' => '0700000000']);
        $this->payer($this->pro, 1);
        $this->actingAs($this->patron);

        $this->get(route('settings'))
            ->assertOk()
            ->assertSee('Mon abonnement Maquis Express')
            ->assertSee('Pro')
            ->assertSee('18/12/2026')
            ->assertSee('Wave : <strong>0700000000</strong>', false)
            ->assertSee($this->maquis->paymentReference());
    }

    public function test_rappel_au_patron_avant_l_echeance(): void
    {
        $this->payer($this->basic, 1);
        $this->travelTo(now()->setDate(2026, 12, 13)->setTime(10, 0)); // 5 jours avant

        $this->actingAs($this->patron)
            ->get(route('tables.index'))
            ->assertSee('Votre abonnement se termine dans 5 jours')
            ->assertSee('Renouveler');
    }

    public function test_la_page_acces_suspendu_explique_comment_payer(): void
    {
        $this->payer($this->basic, 1);
        $this->travelTo(now()->setDate(2027, 1, 10)->setTime(10, 0));

        $this->actingAs($this->patron)
            ->get(route('account.locked'))
            ->assertSee("n'a pas été renouvelé", false)
            ->assertSee($this->maquis->paymentReference());
    }

    public function test_reactiver_a_la_main_un_impaye_le_debloque(): void
    {
        $this->payer($this->basic, 1);
        $this->travelTo(now()->setDate(2027, 1, 10)->setTime(10, 0));
        $this->actingAs($this->admin);

        Livewire::test(RestaurantsList::class)->call('toggleStatus', $this->maquis->id);

        $this->assertFalse($this->maquis->fresh()->isLocked());
    }
}
