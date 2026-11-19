<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Photos envoyées par le téléphone (déjà réduites en JPEG par le navigateur) :
 * on vérifie que c'est bien une image, puis on la range dans un dossier privé.
 */
class PhotoStore
{
    /**
     * @return array{0: ?string, 1: ?string} [chemin du fichier, message d'erreur]
     */
    public static function store(string $dataUrl, string $folder, string $prefix, int $maxSide = 1024, int $maxBytes = 400_000): array
    {
        if (! preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            return [null, 'Choisissez une photo (JPEG, PNG ou WebP).'];
        }

        $binary = base64_decode($m[1], true);

        if ($binary === false || strlen($binary) > $maxBytes) {
            return [null, 'Photo trop lourde. Reprenez la photo.'];
        }

        $info = @getimagesizefromstring($binary);

        if (! $info || $info[2] !== IMAGETYPE_JPEG || $info[0] > $maxSide || $info[1] > $maxSide || $info[0] < 32 || $info[1] < 32) {
            return [null, 'Ce fichier n\'est pas une photo valable. Choisissez-en une autre.'];
        }

        $path = trim($folder, '/') . '/' . $prefix . '-' . Str::random(16) . '.jpg';
        Storage::disk('local')->put($path, $binary);

        return [$path, null];
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    /** Réponse HTTP pour afficher une photo privée. */
    public static function response(string $path)
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
