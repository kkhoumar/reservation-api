<?php

namespace Tests;

use App\Models\CashRegister;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ouvre la caisse du maquis (comme la caissière en début de service) :
     * sans caisse ouverte, aucun encaissement n'est accepté.
     */
    protected function openRegister(Restaurant $restaurant, ?User $by = null, int $openingAmount = 0): CashRegister
    {
        $open = CashRegister::withoutGlobalScopes()->where('restaurant_id', $restaurant->id)->whereNull('closed_at')->first();

        return $open ?? CashRegister::factory()->create([
            'restaurant_id' => $restaurant->id,
            'opened_by' => $by?->id ?? User::factory()->cashier()->create(['restaurant_id' => $restaurant->id])->id,
            'opening_amount' => $openingAmount,
            'opened_at' => now()->subDays(3),
        ]);
    }
}
