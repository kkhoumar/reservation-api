@props(['inComponent' => true])
{{--
    Messages de succès / d'erreur : ils s'affichent en bas de l'écran (voir la
    zone « toasts » du layout). Ce bloc invisible les y envoie ; le texte reste
    dans la page pour les lecteurs d'écran et les tests.
--}}
@php
    $toastId = session('toast_id');
    $undo = session('toast_undo');
@endphp
@foreach (['success', 'error'] as $type)
    @if (session($type))
        @php
            $text = (string) session($type);
            $payload = [
                'id' => ($toastId ?: substr(md5($text), 0, 10)) . '-' . $type,
                'type' => $type,
                'text' => $text,
                'undo' => $type === 'success' ? $undo : null,
                // Message d'une redirection (sans identifiant) : on ne le retient pas, il pourra revenir plus tard
                'persist' => (bool) $toastId,
            ];
        @endphp
        <div hidden class="flash-src" wire:key="flash-{{ $payload['id'] }}"
             x-data x-init="$store.toasts?.push(@js($payload), {{ $inComponent ? '$wire' : 'null' }})">{{ $text }}</div>
    @endif
@endforeach
