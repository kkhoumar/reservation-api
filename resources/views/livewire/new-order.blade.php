<div class="new-order {{ $order && $order->items->isNotEmpty() ? 'has-items' : '' }}">
    <style>
        .new-order .cart { position: sticky; top: 76px; }
        .new-order .order-bar { display: none; }
        @media (max-width: 860px) {
            .new-order .cart { position: static; scroll-margin-top: 70px; }
            .new-order .product-card { padding: 14px 12px; }
            .new-order.has-items { padding-bottom: 84px; }
            .new-order .order-bar {
                display: flex; align-items: center; justify-content: space-between; gap: 12px;
                position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
                background: white; border-top: 1px solid var(--color-border);
                box-shadow: 0 -8px 24px rgba(6,46,36,.10);
                padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
            }
            .new-order .order-bar small { display: block; font-size: 12px; color: var(--color-text-secondary); }
            .new-order .order-bar strong { font-size: 18px; font-family: var(--font-display); }
        }
    </style>
    @if (session('error'))
        <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px;">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px;">
            {{ $errors->first() }}
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
                @continue($category->products->isEmpty())
                <h3 style="color: var(--color-text-secondary);">{{ $category->name }}</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px;">
                    @foreach ($category->products as $product)
                                        <div class="card product-card" style="text-align: center;">
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
        <div class="card cart" id="panier">
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
                            <button wire:click="removeItem({{ $item->id }})" aria-label="Retirer" style="background: var(--color-danger-bg); border: none; color: var(--color-danger); cursor: pointer; width: 34px; height: 34px; border-radius: 10px; font-size: 15px; flex-shrink: 0;">✕</button>
                        </div>
                    </div>
                @endforeach

                <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 18px; margin-top: 12px;">
                    <span>Total</span>
                    <span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                </div>

                <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 8px;">
                @if ($paysBefore)
                    {{-- Paiement avant service : on encaisse, puis on sert --}}
                    @if ($canCollect)
                        {{-- Patron, gérante, caissière : ils encaissent eux-mêmes --}}
                        <button wire:click="goToPayment" class="btn btn-primary" style="justify-content: center; font-size: 16px; padding: 14px 18px;">Encaisser</button>
                        @if ($hasServers)
                            <button wire:click="sendToCounter" class="btn btn-ghost" style="justify-content: center;">Envoyer au comptoir</button>
                        @endif
                    @else
                        <button wire:click="sendToCounter" class="btn btn-primary" style="justify-content: center; font-size: 16px; padding: 14px 18px;">Envoyer au comptoir</button>
                        <div style="font-size: 12px; color: var(--color-text-secondary);">
                            Apporte l'argent au comptoir : le bon est servi une fois encaissé.
                        </div>
                    @endif
                @else
                    @if ($order->status === 'created')
                        <button wire:click="sendToKitchen" class="btn btn-secondary">Envoyer en cuisine</button>
                    @elseif ($order->status === 'in_preparation')
                        <button wire:click="markServed" class="btn btn-secondary">Marquer servie</button>
                    @endif

                    @if (in_array($order->status, ['served', 'in_preparation', 'created']))
                        <button wire:click="goToPayment" class="btn btn-primary">Passer au paiement</button>
                    @endif
                @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Téléphone : le total reste visible en bas, un appui mène à la commande --}}
    @if ($order && $order->items->isNotEmpty())
        <div class="order-bar">
            <div>
                <small>{{ $order->items->sum('quantity') }} article{{ $order->items->sum('quantity') > 1 ? 's' : '' }}</small>
                <strong>{{ number_format($order->total, 0, ',', ' ') }} FCFA</strong>
            </div>
            <a href="#panier" class="btn btn-primary" style="text-decoration: none; padding: 12px 18px; font-size: 15px;">Voir la commande ↓</a>
        </div>
    @endif
</div>
