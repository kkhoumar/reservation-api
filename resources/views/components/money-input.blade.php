{{--
    Champ de montant : affiche « 25 000 » pendant la saisie (séparateur des milliers),
    envoie le nombre brut (25000) à Livewire, et ne se fait jamais écraser par une
    réponse du serveur pendant que l'on tape.

    <x-money-input model="amount" />                  envoyé avec le formulaire
    <x-money-input model="amountReceived" live />     recalcul à la volée (monnaie à rendre…)
    <x-money-input model="openingAmount" :empty="0" />  valeur si le champ est vidé
--}}
@props(['model', 'live' => false, 'empty' => null])

<input type="text" inputmode="numeric" autocomplete="off" enterkeyhint="done" wire:ignore
       x-data="moneyInput(@js($model), @js((bool) $live), @js($empty))"
       x-on:input="onInput()" x-on:blur="onBlur()"
       {{ $attributes }}>
