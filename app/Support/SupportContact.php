<?php

namespace App\Support;

use App\Models\PlatformSetting;
use App\Models\User;

/**
 * Service client Maquis Express sur WhatsApp.
 * Le numéro choisi par le Super Admin passe avant celui du fichier .env.
 */
class SupportContact
{
    public static function whatsappNumber(): ?string
    {
        try {
            $saved = PlatformSetting::read(PlatformSetting::SUPPORT_WHATSAPP);
        } catch (\Throwable) {
            $saved = null; // table pas encore créée (première installation)
        }

        $number = User::normalizePhone($saved ?? config('maquis.billing.whatsapp'));

        return $number !== '' ? $number : null;
    }

    /** Lien qui ouvre WhatsApp sur la conversation, message déjà écrit. */
    public static function whatsappUrl(string $message): ?string
    {
        $number = static::whatsappNumber();

        return $number ? 'https://wa.me/225' . $number . '?text=' . rawurlencode($message) : null;
    }
}
