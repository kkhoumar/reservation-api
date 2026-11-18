{{-- Offrir / remise sur $order (patron et gérante) --}}
@php
    $reasons = ['Bon client', 'Tournée du patron', 'Erreur de service', 'Fidélité'];
@endphp

<div style="padding-top: 12px; border-top: 1px dashed var(--color-border);">
    <div style="font-size: 13px; font-weight: 700; margin-bottom: 8px;">Offrir un article</div>

    @foreach ($order->items as $item)
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
            <span>
                {{ $item->quantity }} × {{ $item->product?->name }}
                @if ($item->is_offered)
                    <span style="font-size: 12px; font-weight: 700; color: var(--color-primary);">· OFFERT</span>
                    <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">
                        {{ $item->offered_reason }} · par {{ $item->offeredBy?->first_name ?? '—' }}
                    </span>
                @endif
            </span>
            @if ($item->is_offered)
                <span style="display: flex; align-items: center; gap: 8px;">
                    <s style="color: var(--color-text-secondary);">{{ number_format($item->fullValue(), 0, ',', ' ') }}</s>
                    <button wire:click="cancelOffer({{ $item->id }})" class="btn btn-ghost" style="padding: 4px 10px; font-size: 12px;">Annuler</button>
                </span>
            @elseif ($offerItemId === $item->id)
                <span style="color: var(--color-text-secondary);">{{ number_format($item->total, 0, ',', ' ') }}</span>
            @else
                <span style="display: flex; align-items: center; gap: 8px;">
                    <span>{{ number_format($item->total, 0, ',', ' ') }}</span>
                    <button wire:click="selectOfferItem({{ $item->id }})" class="btn btn-ghost" style="padding: 4px 10px; font-size: 12px;">Offrir</button>
                </span>
            @endif
        </div>

        @if ($offerItemId === $item->id)
            <div style="background: var(--color-bg); border-radius: 8px; padding: 10px; margin: 6px 0;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end;">
                    @if ($item->quantity > 1)
                        <div>
                            <label style="font-size: 12px; font-weight: 600; display: block;">Combien ?</label>
                            <input type="number" wire:model="offerQuantity" min="1" max="{{ $item->quantity }}" style="width: 80px;">
                        </div>
                    @endif
                    <div style="flex: 1; min-width: 180px;">
                        <label style="font-size: 12px; font-weight: 600; display: block;">Motif</label>
                        <input type="text" wire:model="offerReason" maxlength="255" placeholder="Pourquoi c'est offert ?" style="width: 100%;">
                    </div>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
                    @foreach ($reasons as $reason)
                        <button type="button" wire:click="$set('offerReason', @js($reason))" style="background: white; border: 1px solid var(--color-border); border-radius: 999px; padding: 3px 10px; font-size: 12px; cursor: pointer;">{{ $reason }}</button>
                    @endforeach
                </div>
                @error('offerQuantity') <div style="color: var(--color-danger); font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                @error('offerReason') <div style="color: var(--color-danger); font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                <div style="display: flex; gap: 8px; margin-top: 8px;">
                    <button wire:click="confirmOffer" class="btn btn-secondary" style="padding: 6px 14px; font-size: 13px;">Offrir</button>
                    <button wire:click="$set('offerItemId', null)" class="btn btn-ghost" style="padding: 6px 14px; font-size: 13px;">Retour</button>
                </div>
            </div>
        @endif
    @endforeach

    <div style="font-size: 13px; font-weight: 700; margin: 14px 0 8px 0;">Remise sur le bon</div>
    @if ($order->discount_amount > 0)
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: 14px; margin-bottom: 8px;">
            <span>
                Remise de <strong>{{ number_format($order->discount_amount, 0, ',', ' ') }} FCFA</strong>
                <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">{{ $order->discount_reason }} · par {{ $order->discountedBy?->first_name ?? '—' }}</span>
            </span>
            <button wire:click="removeDiscount" class="btn btn-ghost" style="padding: 4px 10px; font-size: 12px;">Retirer</button>
        </div>
    @else
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end;">
            <div>
                <label style="font-size: 12px; font-weight: 600; display: block;">Montant (FCFA)</label>
                <input type="number" wire:model="discountInput" min="1" style="width: 120px;">
            </div>
            <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 12px; font-weight: 600; display: block;">Motif</label>
                <input type="text" wire:model="discountReason" maxlength="255" placeholder="Ex : groupe de 10" style="width: 100%;">
            </div>
            <button wire:click="applyDiscount" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px;">Appliquer</button>
        </div>
        @error('discountInput') <div style="color: var(--color-danger); font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
        @error('discountReason') <div style="color: var(--color-danger); font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
    @endif
</div>
