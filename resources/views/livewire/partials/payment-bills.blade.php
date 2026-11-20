{{-- Billets donnés par le client : un toucher au lieu de taper (variables du bloc de paiement) --}}
@if ($cashDue > 0 && (! $isMobile || $mixed || $cashDue > 0))
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;" aria-label="Billet donné par le client">
        <button type="button" wire:click="setCashReceived({{ $cashDue }})" style="{{ $chip }} {{ $cash === $cashDue ? $on : $off }}">Compte juste</button>
        @foreach ($bills as $bill)
            <button type="button" wire:click="setCashReceived({{ $bill }})" style="{{ $chip }} {{ $cash === $bill ? $on : $off }}">{{ $fcfa($bill) }}</button>
        @endforeach
    </div>
@endif
