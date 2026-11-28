<?php

namespace Tests\Feature;

use App\Actions\CreateRestaurantWithOwner;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Essai gratuit de 30 jours : le patron lit « 30 jours » le jour de son
 * inscription, quelle que soit l'heure, puis un jour de moins chaque jour.
 */
class TrialDaysTest extends TestCase
{
    use RefreshDatabase;

    private function nouveauMaquis(): Restaurant
    {
        return app(CreateRestaurantWithOwner::class)->handle(
            ['name' => 'Maquis Essai', 'type' => 'maquis', 'phone' => '0707070707', 'commune' => null, 'city' => 'Abidjan', 'trial_days' => Restaurant::DEFAULT_TRIAL_DAYS],
            ['first_name' => 'Awa', 'last_name' => 'Koné', 'phone' => '0708080808', 'password' => 'secret-123'],
        )['restaurant'];
    }

    public function test_l_essai_dure_30_jours(): void
    {
        $this->assertSame(30, Restaurant::DEFAULT_TRIAL_DAYS);
    }

    public function test_le_jour_de_l_inscription_il_reste_30_jours_meme_le_soir(): void
    {
        foreach (['08:00', '23:30'] as $heure) {
            $this->travelTo(now()->setDate(2026, 11, 28)->setTimeFromTimeString($heure));
            $maquis = $this->nouveauMaquis();

            $this->assertSame(30, $maquis->daysLeft(), "Inscription à {$heure}");
            $this->assertSame('28/12/2026', $maquis->trial_ends_at->format('d/m/Y'));

            User::where('restaurant_id', $maquis->id)->delete();
            $maquis->delete();
        }
    }

    public function test_un_jour_de_moins_chaque_jour_jusqu_au_dernier(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 28)->setTime(10, 0));
        $maquis = $this->nouveauMaquis();

        $this->travelTo(now()->setDate(2026, 11, 29)->setTime(7, 0));
        $this->assertSame(29, $maquis->daysLeft());

        $this->travelTo(now()->setDate(2026, 12, 28)->setTime(22, 0));
        $this->assertSame(0, $maquis->daysLeft()); // « se termine aujourd'hui »

        $this->travelTo(now()->setDate(2026, 12, 29)->setTime(9, 0));
        $this->assertSame(-1, $maquis->daysLeft());
    }

    public function test_le_patron_lit_30_jours_dans_l_application(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 28)->setTime(18, 0));
        $maquis = $this->nouveauMaquis();
        $patron = User::where('restaurant_id', $maquis->id)->firstOrFail();
        $patron->forceFill(['terms_version' => config('maquis.terms_version'), 'must_change_password' => false])->save();

        $this->actingAs($patron)->get(route('settings'))
            ->assertOk()
            ->assertSee('encore 30 jours');
    }
}
