<div>
    @if (session('error'))
        <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px;">
            {{ session('error') }}
        </div>
    @endif

    <h1 style="color: var(--color-primary);">Paiement — {{ $order->order_number }}</h1>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card">
            <h3 style="margin-top: 0;">Récapitulatif</h3>
            @if ($order->restaurantTable)
                <p>Table : {{ $order->restaurantTable->label }}</p>
            @else
                <p>À emporter</p>
            @endif

            @foreach ($order->items as $item)
                <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                    <span>{{ $item->quantity }} × {{ $item->product->name }} @if ($item->is_offered)<strong style="font-size: 12px; color: var(--color-primary);">OFFERT</strong>@endif</span>
                    <span>{{ number_format($item->total, 0, ',', ' ') }} FCFA</span>
                </div>
            @endforeach

            @if ($order->discount_amount > 0)
                <div style="display: flex; justify-content: space-between; padding: 6px 0; color: var(--color-primary);">
                    <span>Remise ({{ $order->discount_reason }})</span>
                    <span>−{{ number_format($order->discount_amount, 0, ',', ' ') }} FCFA</span>
                </div>
            @endif

            <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 20px; margin-top: 12px; border-top: 1px solid var(--color-border); padding-top: 12px;">
                <span>Total</span>
                <span style="color: var(--color-accent);">{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
            </div>

            @if ($canGestures && ! $order->isPaid())
                <div style="margin-top: 14px;">
                    <button wire:click="$toggle('showGestures')" class="btn btn-ghost" style="width: 100%; justify-content: center;">
                        {{ $showGestures ? 'Fermer' : 'Offrir / remise' }}
                    </button>
                </div>
                @if ($showGestures)
                    <div style="margin-top: 12px;">
                        @include('livewire.partials.gesture-panel', ['order' => $order])
                    </div>
                @endif
            @endif
        </div>

        <div class="card">
            <h3 style="margin-top: 0;">Encaissement</h3>

            <div style="margin-bottom: 16px;">
                @include('livewire.partials.payment-fields', ['total' => $order->total])
                @error('amountReceived') <div style="color: var(--color-danger); font-size: 13px; margin-top: 8px;">{{ $message }}</div> @enderror
            </div>

            <button wire:click="confirmPayment" class="btn btn-primary" style="width: 100%;">
                Valider le paiement
            </button>

            @if ($canCredit)
                <div style="border-top: 1px dashed var(--color-border); margin-top: 16px; padding-top: 14px;">
                    <label style="font-size: 13px; font-weight: 600;">Ou à crédit pour :</label>
                    <select wire:model="creditCustomerId" style="width: 100%; margin: 6px 0 8px 0;">
                        <option value="">— Choisir le client —</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}</option>
                        @endforeach
                    </select>
                    @error('creditCustomerId') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror
                    <button wire:click="chargeToCustomer" class="btn btn-ghost" style="width: 100%; justify-content: center;">Mettre à crédit</button>
                </div>
            @endif
        </div>
    </div>
</div>