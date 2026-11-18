<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envoi de messages par l'API WhatsApp Business (Meta Cloud API).
 *
 * Une entreprise qui écrit en premier doit utiliser un modèle (« template »)
 * validé par Meta : on envoie le nom du modèle et la valeur de ses variables.
 */
class WhatsAppCloud
{
    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    /** Numéro ivoirien au format attendu par WhatsApp : 225 + 10 chiffres. */
    public static function toInternational(string $phone): string
    {
        return '225' . User::normalizePhone($phone);
    }

    /**
     * Meta refuse dans une variable : retours à la ligne, tabulations,
     * plus de 4 espaces de suite, et valeur vide.
     */
    public static function cleanParameter(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? '—' : mb_substr($value, 0, 900);
    }

    /**
     * @param  list<string>  $parameters  valeurs de {{1}}, {{2}}… dans l'ordre
     * @return string identifiant du message chez Meta
     *
     * @throws RuntimeException si WhatsApp refuse ou ne répond pas
     */
    public function sendTemplate(string $phone, string $template, array $parameters, ?string $language = null): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('API WhatsApp non configurée (WHATSAPP_TOKEN / WHATSAPP_PHONE_NUMBER_ID).');
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );

        $response = Http::withToken(config('services.whatsapp.token'))
            ->acceptJson()
            ->timeout(15)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => self::toInternational($phone),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $language ?? config('services.whatsapp.template_language')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(
                            fn (string $p) => ['type' => 'text', 'text' => self::cleanParameter($p)],
                            $parameters,
                        ),
                    ]],
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('WhatsApp a refusé le message : ' . mb_substr((string) $error, 0, 200));
        }

        $id = $response->json('messages.0.id');

        if (! $id) {
            throw new RuntimeException('Réponse WhatsApp inattendue (pas d\'identifiant de message).');
        }

        return $id;
    }
}
