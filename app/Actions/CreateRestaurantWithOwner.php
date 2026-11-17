<?php

namespace App\Actions;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Le Super Admin installe un nouveau client : le maquis et son
 * propriétaire sont créés ensemble, ou pas du tout.
 *
 * Le propriétaire reçoit un mot de passe provisoire, qu'il devra
 * changer à sa première connexion.
 */
class CreateRestaurantWithOwner
{
    /**
     * @param  array{name: string, type: string, phone: string, commune: ?string, city: ?string, trial_days: int, service_mode?: string, credit_enabled?: bool}  $restaurant
     * @param  array{first_name: string, last_name: string, phone: string}  $owner
     * @return array{restaurant: Restaurant, owner: User, password: string}
     */
    public function handle(array $restaurant, array $owner): array
    {
        $password = self::temporaryPassword();

        [$newRestaurant, $newOwner] = DB::transaction(function () use ($restaurant, $owner, $password) {
            $trialDays = $restaurant['trial_days'];

            $newRestaurant = Restaurant::create([
                'name' => $restaurant['name'],
                'type' => $restaurant['type'],
                'phone' => User::normalizePhone($restaurant['phone']),
                'commune' => $restaurant['commune'] ?: null,
                'city' => $restaurant['city'] ?: null,
                'service_mode' => $restaurant['service_mode'] ?? 'both',
                'credit_enabled' => $restaurant['credit_enabled'] ?? false,
                'subscription_status' => $trialDays > 0 ? 'trial' : 'active',
                'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays)->endOfDay() : null,
            ]);

            $newOwner = new User([
                'first_name' => $owner['first_name'],
                'last_name' => $owner['last_name'],
                'phone' => $owner['phone'],
                'password' => $password,
                'role' => 'owner',
                'status' => 'active',
            ]);
            $newOwner->forceFill([
                'restaurant_id' => $newRestaurant->id,
                'must_change_password' => true,
            ])->save();

            return [$newRestaurant, $newOwner];
        });

        Log::info('Super Admin : nouveau maquis créé', [
            'admin_id' => auth()->id(),
            'restaurant_id' => $newRestaurant->id,
            'owner_id' => $newOwner->id,
        ]);

        return ['restaurant' => $newRestaurant, 'owner' => $newOwner, 'password' => $password];
    }

    /**
     * Mot de passe provisoire facile à dicter au téléphone :
     * pas de 0/O, 1/l/I, ex. « Kp7m-Wx3r ».
     */
    public static function temporaryPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $pick = fn (int $n) => implode('', array_map(
            fn () => $alphabet[random_int(0, strlen($alphabet) - 1)],
            range(1, $n),
        ));

        return $pick(4) . '-' . $pick(4);
    }
}
