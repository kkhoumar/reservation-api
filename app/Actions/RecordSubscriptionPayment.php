<?php

namespace App\Actions;

use App\Exceptions\BusinessRuleException;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Le Super Admin enregistre un paiement d'abonnement reçu d'un maquis :
 * l'accès est prolongé de N mois et le maquis est (ré)activé.
 *
 * - Abonnement encore en cours : on prolonge à partir de la date payée
 *   (le maquis ne perd pas les jours déjà payés).
 * - Essai, retard ou suspension : on repart d'aujourd'hui.
 */
class RecordSubscriptionPayment
{
    public const ALLOWED_MONTHS = [1, 3, 6, 12];

    /**
     * @throws BusinessRuleException
     */
    public function handle(
        Restaurant $restaurant,
        Plan $plan,
        int $months,
        string $method,
        User $admin,
        ?int $amount = null,
        ?string $reference = null,
        ?string $note = null,
    ): SubscriptionPayment {
        if (! $admin->isPlatformAdmin()) {
            throw new BusinessRuleException('Seul le Super Admin enregistre les paiements d\'abonnement.');
        }

        if (! in_array($months, self::ALLOWED_MONTHS, true)) {
            throw new BusinessRuleException('Choisissez une durée de 1, 3, 6 ou 12 mois.');
        }

        if (! array_key_exists($method, SubscriptionPayment::METHODS)) {
            throw new BusinessRuleException('Choisissez le moyen de paiement dans la liste.');
        }

        if (! $plan->is_active) {
            throw new BusinessRuleException('Cette formule n\'est plus proposée.');
        }

        $amount ??= $plan->monthly_price * $months;

        if ($amount < 0 || $amount > 100_000_000) {
            throw new BusinessRuleException('Entrez le montant reçu en chiffres, entre 0 et 100 000 000 FCFA.');
        }

        return DB::transaction(function () use ($restaurant, $plan, $months, $method, $admin, $amount, $reference, $note) {
            $restaurant = Restaurant::whereKey($restaurant->id)->lockForUpdate()->firstOrFail();

            $stillRunning = $restaurant->subscription_status === 'active'
                && $restaurant->paid_until !== null
                && $restaurant->paid_until->isFuture();

            $start = $stillRunning ? $restaurant->paid_until->copy() : now();
            $end = $start->copy()->addMonthsNoOverflow($months)->endOfDay();

            $restaurant->forceFill([
                'plan_id' => $plan->id,
                'paid_until' => $end,
                'subscription_status' => 'active',
            ])->save();

            $payment = SubscriptionPayment::create([
                'restaurant_id' => $restaurant->id,
                'plan_id' => $plan->id,
                'amount' => $amount,
                'months' => $months,
                'method' => $method,
                'reference' => $reference ? mb_substr(trim($reference), 0, 100) : null,
                'period_start' => $start,
                'period_end' => $end,
                'recorded_by' => $admin->id,
                'note' => $note ? mb_substr(trim($note), 0, 255) : null,
            ]);

            Log::info('Abonnement : paiement enregistré', [
                'admin_id' => $admin->id,
                'restaurant_id' => $restaurant->id,
                'plan' => $plan->code,
                'months' => $months,
                'amount' => $amount,
                'paid_until' => $end->toDateString(),
            ]);

            return $payment;
        });
    }
}
