<?php

namespace Tests\Feature;

use App\Livewire\Admin\Subscribers;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Page « Abonnés » du Super Admin : dernier paiement, prochaine échéance,
 * retard, onglets, recherche. Les urgences d'abord.
 */
class SubscribersPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['restaurant_id' => null]);
        $this->admin->forceFill(['is_platform_admin' => true])->save();
        $this->pro = Plan::where('code', 'pro')->first() ?? Plan::create(['code' => 'pro', 'name' => 'Pro', 'monthly_price' => 10000, 'sort_order' => 2, 'is_active' => true]);
    }

    private function subscriber(string $name, ?\Carbon\Carbon $paidUntil, string $status = 'active', string $ownerName = 'Koffi'): Restaurant
    {
        $r = Restaurant::factory()->create(['name' => $name, 'subscription_status' => $status]);
        $r->forceFill(['plan_id' => $this->pro->id, 'paid_until' => $status === 'active' ? $paidUntil : null, 'trial_ends_at' => $status === 'trial' ? $paidUntil : null])->save();
        User::factory()->owner()->create(['restaurant_id' => $r->id, 'first_name' => $ownerName]);

        return $r;
    }

    public function test_les_groupes_de_suivi(): void
    {
        $this->assertSame('retard', $this->subscriber('A', now()->subDays(2))->followUpGroup());
        $this->assertSame('bientot', $this->subscriber('B', now()->addDays(3))->followUpGroup());
        $this->assertSame('ajour', $this->subscriber('C', now()->addDays(20))->followUpGroup());
        $this->assertSame('bloques', $this->subscriber('D', now()->subDays(10))->followUpGroup());
        $this->assertSame('essai', $this->subscriber('E', now()->addDays(5), 'trial')->followUpGroup());
    }

    public function test_la_liste_montre_dernier_paiement_et_echeance_urgences_en_haut(): void
    {
        $aJour = $this->subscriber('Maquis À Jour', now()->addDays(20));
        $this->subscriber('Maquis Bientôt', now()->addDays(3));
        $this->subscriber('Maquis Retard', now()->subDays(2));

        SubscriptionPayment::create(['restaurant_id' => $aJour->id, 'plan_id' => $this->pro->id, 'amount' => 10000, 'months' => 1, 'method' => 'wave',
            'period_start' => now()->subDays(10), 'period_end' => now()->addDays(20)]);

        $this->actingAs($this->admin)->get(route('admin.subscribers'))
            ->assertOk()
            ->assertSeeInOrder(['Maquis Retard', 'Maquis Bientôt', 'Maquis À Jour'])
            ->assertSee('en retard de 2 jours')
            ->assertSee('10 000 F · Wave')
            ->assertSee(now()->addDays(20)->format('d/m/Y'))
            ->assertSee('Jamais payé');
    }

    public function test_onglets_et_recherche(): void
    {
        $this->subscriber('Chez Awa', now()->subDays(2), 'active', 'Awa');
        $this->subscriber('Le Baobab', now()->addDays(20), 'active', 'Yao');

        $this->actingAs($this->admin);

        Livewire::test(Subscribers::class)
            ->call('setTab', 'retard')
            ->assertSee('Chez Awa')
            ->assertDontSee('Le Baobab')
            ->call('setTab', 'pas-un-onglet')
            ->assertSet('tab', 'tous')
            ->set('search', 'yao')
            ->assertSee('Le Baobab')
            ->assertDontSee('Chez Awa');
    }

    public function test_reservee_au_super_admin(): void
    {
        $patron = User::factory()->owner()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($patron)->get(route('admin.subscribers'))->assertRedirect();
        Livewire::actingAs($patron)->test(Subscribers::class)->assertForbidden();
    }
}
