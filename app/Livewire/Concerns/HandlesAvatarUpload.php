<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use App\Support\PhotoStore;

/**
 * Photo de profil : le téléphone la réduit (carré de 320 px, JPEG) avant
 * l'envoi, le serveur vérifie que c'est bien une image puis la range dans
 * un dossier privé. Elle n'est servie qu'aux personnes du même maquis.
 */
trait HandlesAvatarUpload
{
    /**
     * @return string|null message d'erreur, null si la photo est enregistrée
     */
    protected function storeAvatar(User $user, string $dataUrl): ?string
    {
        [$path, $error] = PhotoStore::store($dataUrl, 'avatars', (string) $user->id, 1024, 400_000);

        if ($error) {
            return $error;
        }

        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => $path])->save();

        if ($old && $old !== $path) {
            PhotoStore::delete($old);
        }

        return null;
    }

    protected function deleteAvatar(User $user): void
    {
        if ($user->avatar_path) {
            PhotoStore::delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }
    }
}
