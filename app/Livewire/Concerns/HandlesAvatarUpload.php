<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Photo de profil : le téléphone la réduit (carré de 320 px, JPEG) avant
 * l'envoi, le serveur vérifie que c'est bien une image puis la range dans
 * un dossier privé. Elle n'est servie qu'aux personnes du même maquis.
 */
trait HandlesAvatarUpload
{
    /** Taille maximale reçue, en octets (une photo réduite pèse 15 à 40 Ko). */
    protected int $avatarMaxBytes = 400_000;

    /**
     * @return string|null message d'erreur, null si la photo est enregistrée
     */
    protected function storeAvatar(User $user, string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            return 'Choisissez une photo (JPEG, PNG ou WebP).';
        }

        $binary = base64_decode($m[1], true);

        if ($binary === false || strlen($binary) > $this->avatarMaxBytes) {
            return 'Photo trop lourde. Choisissez une autre photo.';
        }

        $info = @getimagesizefromstring($binary);

        if (! $info || $info[2] !== IMAGETYPE_JPEG || $info[0] > 1024 || $info[1] > 1024 || $info[0] < 32 || $info[1] < 32) {
            return 'Ce fichier n\'est pas une photo valable. Choisissez-en une autre.';
        }

        $path = 'avatars/' . $user->id . '-' . Str::random(16) . '.jpg';
        Storage::disk('local')->put($path, $binary);

        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => $path])->save();

        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        return null;
    }

    protected function deleteAvatar(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }
    }
}
