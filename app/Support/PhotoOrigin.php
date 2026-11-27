<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * D'où vient une photo de reçu : prise sur place avec l'appareil photo, ou
 * choisie dans le téléphone (capture Wave / Orange Money, facture WhatsApp…).
 *
 * C'est une indication pour le patron, donnée par le téléphone : elle aide à
 * repérer une vieille photo, ce n'est pas une preuve absolue.
 */
class PhotoOrigin
{
    public const CAMERA = 'camera';

    public const GALLERY = 'gallery';

    /** Au-delà, une photo choisie dans le téléphone est signalée en orange. */
    public const OLD_AFTER_DAYS = 2;

    /**
     * @param  mixed  $lastModified  date du fichier envoyée par le téléphone (millisecondes)
     * @return array{receipt_source: ?string, receipt_taken_at: ?Carbon}
     */
    public static function fromClient(mixed $source, mixed $lastModified): array
    {
        if ($source === self::CAMERA) {
            return ['receipt_source' => self::CAMERA, 'receipt_taken_at' => now()];
        }

        if ($source !== self::GALLERY) {
            return ['receipt_source' => null, 'receipt_taken_at' => null];
        }

        $ms = is_numeric($lastModified) ? (int) $lastModified : 0;
        // Date absente ou absurde (avant 2015, ou dans le futur) : on ne l'affiche pas
        $takenAt = $ms > 1_420_070_400_000 ? Carbon::createFromTimestampMs($ms) : null;
        if ($takenAt && $takenAt->isFuture()) {
            $takenAt = now();
        }

        return ['receipt_source' => self::GALLERY, 'receipt_taken_at' => $takenAt];
    }
}
