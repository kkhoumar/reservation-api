<div>
    @if (session('error'))
        <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px;">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <h1 style="color: var(--color-primary);">
        @if ($tableId)
            Commande — {{ \App\Models\RestaurantTable::find($tableId)?->label }}
        @else
            Commande à emporter
        @endif
        @if ($order)
            <span style="font-size: 16px; color: var(--color-text-secondary);">({{ $order->order_number }})</span>
        @endif
    </h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">

        {{-- Colonne gauche : catégories et produits --}}
        <div>
            @foreach ($categories as $category)
                <h3 style="color: var(--color-text-secondary);">{{ $category->name }}</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px;">
                    @foreach ($category->products as $product)
                                        <div class="card" style="text-align: center;">
                                            <div style="font-weight: 600;">{{ $product->name }}</div>
                <div style="color: var(--color-accent); font-weight: 700; margin: 6px 0;">
                    {{ number_format($product->selling_price, 0, ',', ' ') }} FCFA
                </div>
                @if ($product->stock_quantity <= 0)
                    <div style="font-size: 11px; color: var(--color-danger); font-weight: 600; margin-bottom: 6px;">Rupture de stock</div>
                    <button class="btn btn-ghost" style="width: 100%; opacity: 0.5; cursor: not-allowed;" disabled>Indisponible</button>
                @elseif ($product->isLowStock())
                    <div style="font-size: 11px; color: var(--color-accent); font-weight: 600; margin-bottom: 6px;">⚠️ Stock faible ({{ $product->stock_quantity }})</div>
                    <button wire:click="addProduct({{ $product->id }})" class="btn btn-primary" style="width: 100%;">Ajouter</button>
                @else
                    <button wire:click="addProduct({{ $product->id }})" class="btn btn-primary" style="width: 100%;">Ajouter</button>
                @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Colonne droite : panier / commande en cours --}}
        <div class="card">
            <h3 style="margin-top: 0;">Commande en cours</h3>

            @if (!$order || $order->items->isEmpty())
                <p style="color: var(--color-text-secondary);">Aucun article pour le moment.</p>
            @else
                @foreach ($order->items as $item)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--color-border);">
                        <div>
                            <div>{{ $item->quantity }} × {{ $item->product->name }}</div>
                            @if ($item->note)
                                <div style="font-size: 12px; color: var(--color-text-secondary);">{{ $item->note }}</div>
                            @endif
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>{{ number_format($item->total, 0, ',', ' ') }} FCFA</span>
                            <button wire:click="removeItem({{ $item->id }})" style="background: none; border: none; color: var(--color-danger); cursor: pointer;">✕</button>
                        </div>
                    </div>
                @endforeach

                <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 18px; margin-top: 12px;">
                    <span>Total</span>
                    <span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                </div>

                <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 8px;">
                    @if ($order->status === 'created')
                        <button wire:click="sendToKitchen" class="btn btn-secondary">Envoyer en cuisine</button>
                    @elseif ($order->status === 'in_preparation')
                        <button wire:click="markServed" class="btn btn-secondary">Marquer servie</button>
                    @endif

                    @if (in_array($order->status, ['served', 'in_preparation', 'created']))
                        <button wire:click="goToPayment" class="btn btn-primary">Passer au paiement</button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
