{{-- Pastille d'un produit : bouteille pour les boissons, couverts pour la nourriture --}}
@props(['kind' => 'food'])
<span {{ $attributes->merge(['class' => 'sc-ava ' . ($kind === 'drink' ? 'drink' : 'food')]) }} aria-hidden="true"><x-icon :name="$kind === 'drink' ? 'bottle' : 'utensils'" size="19" /></span>
