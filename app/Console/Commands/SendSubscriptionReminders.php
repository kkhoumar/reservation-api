<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Models\SubscriptionReminder;
use App\Services\WhatsAppCloud;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

/**
 * Chaque matin : rappelle au patron que son abonnement (ou son essai) arrive
 * à échéance — J-7, J-3, J-1, jour J, pendant la grâce, puis à la coupure.
 * Un rappel n'est jamais envoyé deux fois pour la même étape et la même échéance.
 *
 * Sans API WhatsApp configurée, le rappel est noté « à envoyer à la main » :
 * le Super Admin le voit dans sa liste avec un bouton WhatsApp prêt.
 */
class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:remind';

    protected $description = 'Envoie aux patrons les rappels d\'échéance d\'abonnement';

    public function handle(WhatsAppCloud $whatsapp): int
    {
        $sent = 0;
        $manual = 0;

        Restaurant::with('plan')
            ->whereIn('subscription_status', ['trial', 'active'])
            ->each(function (Restaurant $restaurant) use ($whatsapp, &$sent, &$manual) {
                $kind = $restaurant->reminderKind();

                if (! $kind) {
                    return;
                }

                $dueDate = $restaurant->accessEndsAt()->toDateString();

                $alreadyDone = SubscriptionReminder::where('restaurant_id', $restaurant->id)
                    ->where('kind', $kind)
                    ->whereDate('due_date', $dueDate)
                    ->exists();

                if ($alreadyDone) {
                    return;
                }

                // On réserve le rappel AVANT d'envoyer : si la tâche tourne deux
                // fois en même temps, la contrainte unique empêche un double envoi
                try {
                    $reminder = SubscriptionReminder::create([
                        'restaurant_id' => $restaurant->id,
                        'kind' => $kind,
                        'due_date' => $dueDate,
                        'status' => 'pending',
                    ]);
                } catch (UniqueConstraintViolationException) {
                    return;
                }

                $phone = $restaurant->ownerPhone();
                $status = 'manual';
                $error = null;

                if ($phone && $whatsapp->isConfigured()) {
                    try {
                        $whatsapp->sendTemplate($phone, config('services.whatsapp.reminder_template'), [
                            $restaurant->name,
                            $restaurant->reminderSituation(),
                            $restaurant->plan
                                ? "{$restaurant->plan->name} : " . number_format($restaurant->plan->monthly_price, 0, ',', ' ') . ' FCFA/mois'
                                : 'votre formule',
                            $restaurant->paymentReference(),
                            Restaurant::howToPayLine(),
                        ]);
                        $status = 'sent';
                        $sent++;
                    } catch (Throwable $e) {
                        $status = 'failed';
                        $error = mb_substr($e->getMessage(), 0, 255);
                    }
                } else {
                    $error = $phone ? 'API WhatsApp non configurée' : 'Aucun numéro de patron';
                }

                if ($status !== 'sent') {
                    $manual++;
                }

                $reminder->update([
                    'status' => $status,
                    'phone' => $phone,
                    'error' => $error,
                ]);
            });

        $this->info("Rappels envoyés : {$sent} · à envoyer à la main : {$manual}");

        return self::SUCCESS;
    }
}
