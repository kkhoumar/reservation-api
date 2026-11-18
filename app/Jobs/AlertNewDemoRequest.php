<?php

namespace App\Jobs;

use App\Models\DemoRequest;
use App\Services\WhatsAppCloud;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Prévient le Super Admin qu'un prospect vient de demander une démo,
 * pour le rappeler vite. Deux canaux, chacun actif s'il est configuré :
 * e-mail (PLATFORM_ALERT_EMAIL) et WhatsApp (PLATFORM_ALERT_WHATSAPP + API Meta).
 * Un canal en panne n'empêche pas l'autre.
 */
class AlertNewDemoRequest implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public int $demoRequestId)
    {
    }

    public function handle(WhatsAppCloud $whatsapp): void
    {
        $demo = DemoRequest::find($this->demoRequestId);

        if (! $demo) {
            return;
        }

        $this->sendEmail($demo);
        $this->sendWhatsapp($demo, $whatsapp);
    }

    protected function sendEmail(DemoRequest $demo): void
    {
        $to = config('maquis.alerts.email');

        if (! $to) {
            return;
        }

        try {
            Mail::raw($this->text($demo), function ($message) use ($to, $demo) {
                $message->to($to)->subject("Nouvelle demande de démo : {$demo->restaurant_name}");
            });
        } catch (Throwable $e) {
            Log::warning('Alerte démo : e-mail non envoyé', ['demo_request_id' => $demo->id, 'error' => $e->getMessage()]);
        }
    }

    protected function sendWhatsapp(DemoRequest $demo, WhatsAppCloud $whatsapp): void
    {
        $to = config('maquis.alerts.whatsapp');

        if (! $to || ! $whatsapp->isConfigured()) {
            return;
        }

        try {
            $whatsapp->sendTemplate($to, config('services.whatsapp.demo_template'), [
                $demo->contact_name,
                $demo->restaurant_name,
                $demo->phone,
                $demo->commune ?: '—',
                $demo->message ?: '—',
            ]);
        } catch (Throwable $e) {
            Log::warning('Alerte démo : WhatsApp non envoyé', ['demo_request_id' => $demo->id, 'error' => $e->getMessage()]);
        }
    }

    protected function text(DemoRequest $demo): string
    {
        return implode("\n", array_filter([
            'Nouvelle demande de démo sur Maquis Express',
            '',
            "Nom : {$demo->contact_name}",
            "Établissement : {$demo->restaurant_name}",
            "Téléphone / WhatsApp : {$demo->phone}",
            $demo->commune ? "Commune : {$demo->commune}" : null,
            $demo->message ? "Message : {$demo->message}" : null,
            '',
            'Rappelez-le vite : ' . route('admin.restaurants'),
            'WhatsApp direct : https://wa.me/225' . $demo->phone,
        ], fn ($l) => $l !== null));
    }
}
