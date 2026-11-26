{{--
    Petite image d'un produit : sa vraie photo si le patron en a pris une,
    sinon le dessin (choisi ou deviné d'après le nom).
    <x-product-icon :product="$product" :category="$category" size="40" />
    Ou sans modèle (lignes d'un comptage) : <x-product-icon :name="$nom" :kind="$famille" />
--}}
@props(['product' => null, 'category' => null, 'name' => '', 'kind' => null, 'icon' => null, 'size' => 40, 'photo' => true])
@php
    $key = $icon && \App\Support\ProductIcon::isValid($icon)
        ? $icon
        : ($product ? $product->iconKey($category) : \App\Support\ProductIcon::guess((string) $name, null, $kind));
    $url = $photo && $product ? $product->photoUrl() : null;
@endphp
<span {{ $attributes->merge(['class' => 'pi']) }} style="width: {{ $size }}px; height: {{ $size }}px; --c: {{ \App\Support\ProductIcon::color($key) }};" aria-hidden="true">
    @if ($url)
        <img src="{{ $url }}" alt="" loading="lazy" decoding="async">
    @else
        <svg viewBox="0 0 64 64"><use href="#pi-{{ \App\Support\ProductIcon::shape($key) }}"/></svg>
    @endif
</span>
