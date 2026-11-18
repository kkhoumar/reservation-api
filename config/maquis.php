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

    // Alerte au Super Admin à chaque nouvelle demande de démo
    'alerts' => [
        'email' => env('PLATFORM_ALERT_EMAIL'),
        'whatsapp' => env('PLATFORM_ALERT_WHATSAPP'), // format 0700000000
    ],

    // Identité de l'éditeur, affichée dans les conditions et la politique de
    // confidentialité. Vide = « [à compléter] » à l'écran.
    'legal' => [
        'company' => env('PLATFORM_LEGAL_NAME'),
        'form' => env('PLATFORM_LEGAL_FORM'),         // ex. SARL au capital de …
        'rccm' => env('PLATFORM_LEGAL_RCCM'),         // n° RCCM
        'address' => env('PLATFORM_LEGAL_ADDRESS'),
        'email' => env('PLATFORM_LEGAL_EMAIL'),
        'phone' => env('PLATFORM_LEGAL_PHONE'),
        'host' => env('PLATFORM_LEGAL_HOST'),         // hébergeur des données
        'artci' => env('PLATFORM_ARTCI_REFERENCE'),   // n° de déclaration/autorisation ARTCI
    ],

    // À changer à chaque modification des conditions : les patrons
    // devront les accepter de nouveau à leur prochaine connexion.
    'terms_version' => '2026-11-18',

];
