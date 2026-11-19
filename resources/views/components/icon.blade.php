{{--
    Icônes Maquis Express : un seul style partout (trait fin, bouts arrondis),
    dessinées dans le code. Identiques sur tous les téléphones, elles prennent
    la couleur du texte autour (currentColor).

    Utilisation : <x-icon name="bottle" />  ou  <x-icon name="check" size="16" />
--}}
@props(['name', 'size' => 22, 'stroke' => 1.9])

@php
    $paths = [
        // --- Signaux ---
        'check'        => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9.5"/><path d="m8.5 12.2 2.4 2.4 4.6-4.8"/>',
        'alert'        => '<path d="M10.3 4.1 2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 4.1a2 2 0 0 0-3.4 0z"/><path d="M12 9.5v4"/><path d="M12 17h.01"/>',
        'x'            => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'clock'        => '<circle cx="12" cy="12" r="9.5"/><path d="M12 7v5l3.2 2"/>',
        'bell'         => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
        'lock'         => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'file'         => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',

        // --- Maquis ---
        'bottle'       => '<path d="M10 2h4v3.2l1.3 1.8A4 4 0 0 1 16 9.3V20a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V9.3a4 4 0 0 1 .7-2.3L10 5.2z"/><path d="M8 12.5h8"/><path d="M8 17h8"/>',
        'cash'         => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 12h.01"/><path d="M18 12h.01"/>',
        'cash-missing' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 12h.01"/><path d="M18 12h.01"/><path d="m3 3 18 18"/>',
        'wallet'       => '<path d="M19 7V5.5A1.5 1.5 0 0 0 17.5 4h-12A2.5 2.5 0 0 0 3 6.5v11A2.5 2.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V16"/><path d="M21 9.5h-4.5a2.5 2.5 0 0 0 0 5H21z"/><path d="M3 6.5A2.5 2.5 0 0 0 5.5 9H19"/>',
        'notebook'     => '<path d="M5 4a2 2 0 0 1 2-2h11a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2z"/><path d="M9 2v20"/><path d="M12.5 7h3.5"/><path d="M12.5 11h3.5"/>',
        'box'          => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5"/><path d="M12 13v8"/><path d="m7.5 5.5 9 5"/>',
        'receipt'      => '<path d="M5 2h14v20l-2.5-1.6L14 22l-2-1.6L10 22l-2.5-1.6L5 22z"/><path d="M9 7h6"/><path d="M9 11h6"/><path d="M9 15h3.5"/>',
        'phone'        => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
        'chart'        => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5.5-6"/><path d="M15.5 8h4v4"/>',
        'message'      => '<path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.6L3 21l1.9-5.3A8.5 8.5 0 1 1 21 11.5z"/><path d="M8.5 11.5h.01"/><path d="M12.5 11.5h.01"/><path d="M16.5 11.5h.01"/>',
        'gift'         => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C10.5 3 12 8 12 8s1.5-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'sheet'        => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 12.5h8"/><path d="M8 16.5h8"/><path d="M12 12.5v8"/>',
        'download'     => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',

        // --- Rôles ---
        'service'      => '<path d="M3 18h18"/><path d="M5 18a7 7 0 0 1 14 0"/><path d="M12 11V9"/><path d="M10.5 9h3"/>',
        'clipboard'    => '<rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 2h6v4H9z"/><path d="M9 11h6"/><path d="M9 15h6"/>',
        'crown'        => '<path d="M3 7.5 7.5 12 12 4.5l4.5 7.5L21 7.5 19 18H5z"/><path d="M5 21h14"/>',
    ];
    $svg = $paths[$name] ?? $paths['alert'];
@endphp

<svg {{ $attributes->merge(['class' => 'me-icon', 'style' => 'vertical-align: -0.18em; flex-shrink: 0;']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">{!! $svg !!}</svg>
