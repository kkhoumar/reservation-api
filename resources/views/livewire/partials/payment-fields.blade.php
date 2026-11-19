{{-- Champs d'encaissement : $total (total du bon) --}}
@php
    $methods = \App\Models\Payment::METHODS;
    $isMobile = $paymentMethod !== 'cash';
    $mobile = $isMobile ? (int) $mobileAmount : 0;
    $cashDue = max(0, $total - $mobile);
    $change = max(0, (int) $amountReceived - $cashDue);
@endphp

<div>
    <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Mode de paiement</label>
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
        @foreach ($methods as $value => $label)
            <label style="padding: 7px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid {{ $paymentMethod === $value ? 'var(--color-primary)' : 'var(--color-border)' }}; background: {{ $paymentMethod === $value ? 'var(--color-primary)' : 'white' }}; color: {{ $paymentMethod === $value ? 'white' : 'var(--color-text)' }};">
                <input type="radio" wire:model.live="paymentMethod" value="{{ $value }}" style="display: none;">
                {{ $label }}
            </label>
        @endforeach
    </div>
    @error('paymentMethod') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror

    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        @if ($isMobile)
            <div>
                <label style="font-size: 13px; font-weight: 600; display: block;">Montant {{ $methods[$paymentMethod] }} (FCFA)</label>
                <x-money-input model="mobileAmount" live style="font-size: 18px; width: 170px; margin-top: 4px;" />
            </div>
            <div>
                <label style="font-size: 13px; font-weight: 600; display: block;">Référence de la transaction</label>
                <input type="text" wire:model="reference" maxlength="100" placeholder="Facultatif" style="width: 170px; margin-top: 4px;">
            </div>
        @endif

        @if (! $isMobile || $cashDue > 0)
            <div>
                <label style="font-size: 13px; font-weight: 600; display: block;">
                    {{ $isMobile ? 'Espèces reçues pour le reste' : 'Espèces reçues' }} (FCFA)
                </label>
                <x-money-input model="amountReceived" live style="font-size: 18px; width: 170px; margin-top: 4px;" />
            </div>
        @endif
    </div>
    @error('mobileAmount') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
    @error('reference') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror

    <div style="font-size: 14px; margin-top: 10px; display: flex; gap: 16px; flex-wrap: wrap;">
        @if ($isMobile && $cashDue > 0)
            <span>Reste en espèces : <strong>{{ number_format($cashDue, 0, ',', ' ') }} FCFA</strong></span>
        @endif
        @if (! $isMobile || $cashDue > 0)
            <span>Monnaie à rendre : <strong style="color: var(--color-accent);">{{ number_format($change, 0, ',', ' ') }} FCFA</strong></span>
        @endif
        @if ($isMobile)
            <span style="color: var(--color-text-secondary); font-size: 13px;">Vérifiez que le paiement est bien arrivé sur le téléphone du maquis.</span>
        @endif
    </div>
</div>
