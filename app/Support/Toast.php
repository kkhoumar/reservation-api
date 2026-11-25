<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Message affiché en bas de l'écran après une action :
 * - succès : vert, disparaît tout seul (avec un bouton « Annuler » si on peut revenir en arrière) ;
 * - erreur : rouge, reste jusqu'à ce qu'on le ferme.
 *
 * Le message passe par la session (clés « success » / « error ») : il survit
 * à une redirection et s'affiche sur la page suivante.
 */
final class Toast
{
    /**
     * @param  array{call: string, args?: array<int, mixed>}|null  $undo  action du composant à rappeler pour annuler
     */
    public static function success(string $message, ?array $undo = null): void
    {
        self::put('success', $message, $undo);
    }

    public static function error(string $message): void
    {
        self::put('error', $message);
    }

    private static function put(string $type, string $message, ?array $undo = null): void
    {
        session()->flash($type, $message);
        // Un identifiant par message : le même texte affiché deux fois de suite donne bien deux messages,
        // et un message déjà vu ne revient pas à l'action suivante.
        session()->flash('toast_id', Str::random(10));
        session()->flash('toast_undo', $undo);
    }
}
