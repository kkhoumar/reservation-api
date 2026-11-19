{{--
    Photo de profil ronde, ou initiales sur une couleur propre à la personne.
    <x-avatar :user="$user" size="40" />   ou   <x-avatar name="Jean Kouassi" size="32" />
--}}
@props(['user' => null, 'name' => null, 'size' => 40])
@php
    $label = $user ? trim($user->first_name . ' ' . $user->last_name) : trim((string) $name);
    $parts = preg_split('/\s+/', $label ?: '?');
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    $palette = ['#0B4D3C', '#D2622A', '#2463B5', '#8E44AD', '#B7791F', '#1E8E5A', '#C2452D', '#146652'];
    $color = $palette[abs(crc32($user?->id ? 'u' . $user->id : $label)) % count($palette)];
    $photo = $user?->avatarUrl();
@endphp
<span {{ $attributes->merge(['class' => 'me-avatar']) }}
      style="width: {{ $size }}px; height: {{ $size }}px; border-radius: 50%; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; background: {{ $color }}; color: #fff; font-weight: 700; font-size: {{ round($size * 0.38) }}px; line-height: 1; letter-spacing: .02em;"
      title="{{ $label }}" aria-hidden="true">
    @if ($photo)
        <img src="{{ $photo }}" alt="" width="{{ $size }}" height="{{ $size }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block;">
    @else
        {{ $initials }}
    @endif
</span>
