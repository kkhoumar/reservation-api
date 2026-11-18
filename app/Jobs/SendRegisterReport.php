<?php

namespace App\Jobs;

use App\Models\CashRegister;
use App\Services\WhatsAppCloud;
use App\Support\RegisterReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoie automatiquement le rapport du soir au patron sur WhatsApp,
 * juste après la clôture de caisse (en arrière-plan, sans faire attendre
 * la caissière). En cas d'échec, on réessaie ; le bouton manuel reste
 * toujours disponible sur l'écran de clôture.
 */
class SendRegisterReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Secondes d'attente entre deux essais. */
    public array $backoff = [60, 300];

    public function __construct(public int $registerId)
    {
    }

    /** Envoi demandé : on note « en cours » et on lance la tâche. */
    public static function queueFor(CashRegister $register): void
    {
        $register->forceFill([
            'report_status' => 'pending',
            'report_error' => null,
        ])->save();

        static::dispatch($register->id)->afterCommit();
    }

    public function handle(WhatsAppCloud $whatsapp): void
    {
        $register = CashRegister::withoutGlobalScopes()
            ->with(['restaurant', 'openedByUser', 'closedByUser'])
            ->find($this->registerId);

        if (! $register || $register->report_status === 'sent') {
            return;
        }

        $restaurant = $register->restaurant;
        $phone = $restaurant?->reportRecipientPhone();

        if (! $restaurant?->whatsapp_report_enabled || ! $phone || ! $whatsapp->isConfigured()) {
            $register->forceFill([
                'report_status' => 'skipped',
                'report_error' => ! $whatsapp->isConfigured()
                    ? 'Envoi automatique non configuré sur la plateforme.'
                    : (! $phone ? 'Aucun numéro pour recevoir le rapport.' : 'Envoi automatique désactivé par le patron.'),
            ])->save();

            return;
        }

        $report = new RegisterReport($register);

        $whatsapp->sendTemplate(
            $phone,
            config('services.whatsapp.report_template'),
            $report->templateParameters(),
        );

        $register->forceFill([
            'report_status' => 'sent',
            'report_phone' => $phone,
            'report_sent_at' => now(),
            'report_error' => null,
        ])->save();

        Log::info('Rapport du soir envoyé sur WhatsApp', [
            'register_id' => $register->id,
            'restaurant_id' => $register->restaurant_id,
        ]);
    }

    /** Tous les essais ont échoué : on l'affiche sur l'écran de clôture. */
    public function failed(?Throwable $e): void
    {
        CashRegister::withoutGlobalScopes()->whereKey($this->registerId)->update([
            'report_status' => 'failed',
            'report_error' => mb_substr($e?->getMessage() ?? 'Erreur inconnue', 0, 255),
        ]);

        Log::warning('Rapport du soir : envoi WhatsApp échoué', [
            'register_id' => $this->registerId,
            'error' => $e?->getMessage(),
        ]);
    }
}
