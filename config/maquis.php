<?php

/**
 * Réglages de la plateforme Maquis Express (à renseigner dans .env).
 */
return [

    // Où les maquis paient leur abonnement (affiché au patron)
    'billing' => [
        'wave' => env('PLATFORM_WAVE_NUMBER'),
        'orange_money' => env('PLATFORM_ORANGE_NUMBER'),
        'mtn_momo' => env('PLATFORM_MTN_NUMBER'),
        'moov_money' => env('PLATFORM_MOOV_NUMBER'),
        // Numéro WhatsApp du service client (format 0700000000)
        'whatsapp' => env('PLATFORM_WHATSAPP_NUMBER'),
    ],

];
