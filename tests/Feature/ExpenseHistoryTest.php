<?php

namespace Tests\Feature;

use App\Livewire\ExpensesList;
use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Historique des dépenses : le patron et la gérante retrouvent les dépenses
 * passées (période, catégorie) ; la caissière voit celles du jour.
 */
class ExpenseHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $maquis;
    private User $patron;
    private User $jean;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(20, 0));

        $this->maquis = Restaurant::factory()->create();
        $this->patron = User::factory()->owner()->create(['restaurant_id' => $this->maquis->id]);
        $this->jean = User::factory()->cashier()->create(['restaurant_id' => $this->maquis->id, 'first_name' => 'Jean']);

        $this->depense('Glace du jour', 'goods_purchase', 2000, now());
        $this->depense('Taxi marché', 'transport', 1500, now()->subDays(3));
        $this->depense('Facture CIE', 'electricity', 25000, now()->subDays(10));
        $this->depense('Loyer octobre', 'rent', 150000, now()->subMonthNoOverflow()->setDay(5));
    }

    private function depense(string $description, string $category, int $amount, $date): void
    {
        Expense::factory()->create([
            'restaurant_id' => $this->maquis->id,
            'user_id' => $this->jean->id,
            'description' => $description,
            'category' => $category,
            'amount' => $amount,
            'expense_date' => $date,
        ]);
    }

    public function test_par_defaut_on_voit_les_depenses_du_jour(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(ExpensesList::class)
            ->assertSee('Glace du jour')
            ->assertDontSee('Taxi marché')
            ->assertViewHas('total', 2000);
    }

    public function test_le_patron_voit_les_7_derniers_jours_et_le_mois(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(ExpensesList::class)
            ->set('period', '7days')
            ->assertSee('Taxi marché')
            ->assertDontSee('Facture CIE')
            ->assertViewHas('total', 3500)
            ->set('period', 'month')
            ->assertSee('Facture CIE')
            ->assertDontSee('Loyer octobre')
            ->assertViewHas('total', 28500)
            ->set('period', 'last_month')
            ->assertSee('Loyer octobre')
            ->assertViewHas('total', 150000);
    }

    public function test_filtre_par_categorie_et_par_dates(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(ExpensesList::class)
            ->set('period', 'custom')
            ->set('from', '2026-10-01')
            ->set('to', '2026-11-30')
            ->assertViewHas('total', 178500)
            ->set('filterCategory', 'electricity')
            ->assertSee('Facture CIE')
            ->assertDontSee('Loyer octobre')
            ->assertViewHas('total', 25000);
    }

    public function test_l_historique_indique_qui_a_saisi_et_le_total_par_categorie(): void
    {
        $this->actingAs($this->patron);

        Livewire::test(ExpensesList::class)
            ->set('period', 'month')
            ->assertSee('par Jean')
            ->assertSee('Électricité')
            ->assertSee('25 000')
            ->assertSee('Mercredi 18 novembre 2026');
    }

    public function test_la_caissiere_ne_voit_que_le_jour_meme_en_forcant_la_periode(): void
    {
        $this->actingAs($this->jean);

        Livewire::test(ExpensesList::class)
            ->assertDontSee('7 derniers jours')
            ->set('period', 'last_month')
            ->assertDontSee('Loyer octobre')
            ->assertViewHas('total', 2000);
    }

    public function test_les_depenses_d_un_autre_maquis_n_apparaissent_pas(): void
    {
        Expense::factory()->create(['description' => 'Dépense voisin', 'expense_date' => now()]);
        $this->actingAs($this->patron);

        Livewire::test(ExpensesList::class)
            ->set('period', 'month')
            ->assertDontSee('Dépense voisin');
    }
}
