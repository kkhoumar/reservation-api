{{-- Pastille d'un produit dans le stock et le comptage : sa photo ou son dessin --}}
@props(['kind' => 'food', 'product' => null, 'name' => '', 'icon' => null, 'photoUrl' => null])
@if ($photoUrl)
    <span {{ $attributes->merge(['class' => 'pi sc-ava']) }} style="width: 40px; height: 40px;" aria-hidden="true"><img src="{{ $photoUrl }}" alt="" loading="lazy" decoding="async"></span>
@else
    <x-product-icon {{ $attributes->merge(['class' => 'sc-ava']) }} :product="$product" :name="$name" :kind="$kind" :icon="$icon" size="40" />
@endif
