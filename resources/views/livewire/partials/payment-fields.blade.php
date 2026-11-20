{{-- Champs d'encaissement : $total (total du bon) --}}
@php
    $methods = \App\Models\Payment::METHODS;
    $mobileMethods = array_intersect_key($methods, array_flip(\App\Models\Payment::MOBILE_METHODS));
    $isMobile = $paymentMethod !== 'cash';
    $mobile = $isMobile ? (int) $mobileAmount : 0;
    $cashDue = max(0, $total - $mobile);
    $cash = (int) $amountReceived;
    $change = max(0, $cash - $cashDue);
    $missing = max(0, $total - $mobile - $cash);
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    // Billets que le client tend le plus souvent, au-dessus de ce qu'il doit
    $bills = $cashDue > 0 ? array_slice(array_values(array_filter([1000, 2000, 5000, 10000], fn ($b) => $b > $cashDue)), 0, 3) : [];
    $chip = 'padding: 8px 13px; border-radius: 999px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; min-height: 38px;';
    $on = 'border: 1px solid var(--color-primary); background: var(--color-primary); color: white;';
    $off = 'border: 1px solid var(--color-border); background: white; color: var(--color-text);';
@endphp

<div>
    <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Mode de paiement</label>
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @foreach ($methods as $value => $label)
            <button type="button" wire:click="choosePayment('{{ $value }}')" style="{{ $chip }} {{ ! $mixed && $paymentMethod === $value ? $on : $off }}">{{ $label }}</button>
        @endforeach
    </div>
    <button type="button" wire:click="chooseMixed" style="{{ $chip }} {{ $mixed ? $on : $off }} display: inline-flex; align-items: center; gap: 6px; margin-bottom: 14px;">
        <x-icon name="cash" size="16" /> Espèces + Mobile Money
    </button>
    @error('paymentMethod') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror

    @if ($mixed)
        {{-- ===== Paiement mixte : le client donne une partie en espèces, le reste par téléphone ===== --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 6px;">
            <div>
                <label style="font-size: 13px; font-weight: 600; display: block;">Espèces</label>
                <x-money-input model="amountReceived" live placeholder="0" style="font-size: 18px; width: 100%; margin-top: 4px;" />
            </div>
            <div>
                <label style="font-size: 13px; font-weight: 600; display: block;">Mobile Money</label>
                <x-money-input model="mobileAmount" live placeholder="0" style="font-size: 18px; width: 100%; margin-top: 4px;" />
                <select wire:model.live="paymentMethod" aria-label="Opérateur" style="width: 100%; margin-top: 6px;">
                    @foreach ($mobileMethods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @include('livewire.partials.payment-bills')

        <div style="margin-top: 12px;">
            <label style="font-size: 13px; font-weight: 600; display: block;">Référence de la transaction <span style="font-weight: 500; color: var(--color-text-secondary);">facultatif</span></label>
            <input type="text" wire:model="reference" maxlength="100" style="width: 100%; margin-top: 4px;">
        </div>

        <div style="margin-top: 12px; background: var(--color-bg); border-radius: 12px; padding: 10px 14px; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; gap: 10px;"><span>Total du bon</span><strong>{{ $fcfa($total) }} F</strong></div>
            <div style="display: flex; justify-content: space-between; gap: 10px;"><span>Payé</span><strong>{{ $fcfa($mobile + $cash) }} F</strong></div>
            @if ($missing > 0)
                <div style="display: flex; justify-content: space-between; gap: 10px; color: var(--color-danger); font-weight: 700; margin-top: 4px;"><span>Il manque</span><span>{{ $fcfa($missing) }} F</span></div>
            @else
                <div style="display: flex; justify-content: space-between; gap: 10px; color: var(--color-accent); font-weight: 700; margin-top: 4px;"><span>Monnaie à rendre</span><span>{{ $fcfa($change) }} F</span></div>
            @endif
        </div>
    @else
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

        @include('livewire.partials.payment-bills')

        <div style="font-size: 14px; margin-top: 10px; display: flex; gap: 16px; flex-wrap: wrap;">
            @if ($isMobile && $cashDue > 0)
                <span>Reste en espèces : <strong>{{ $fcfa($cashDue) }} FCFA</strong></span>
            @endif
            @if (! $isMobile || $cashDue > 0)
                <span>Monnaie à rendre : <strong style="color: var(--color-accent);">{{ $fcfa($change) }} FCFA</strong></span>
            @endif
        </div>
    @endif


    @error('mobileAmount') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
    @error('reference') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror

    @if ($isMobile)
        <div style="color: var(--color-text-secondary); font-size: 13px; margin-top: 10px;">Vérifiez que le paiement est bien arrivé sur le téléphone du maquis.</div>
    @endif
</div>
