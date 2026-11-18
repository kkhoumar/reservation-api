<div style="display: flex; justify-content: center; padding-top: 20px;">
    <div class="card" style="max-width: 420px; width: 100%; text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(101,185,110,0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0B5D3B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>

        <h1 style="font-size: 20px; margin-bottom: 4px;">Paiement encaissé</h1>
        <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 24px;">
            {{ $order->order_number }} · {{ $order->payment->paid_at->format('d/m/Y à H:i') }}
        </p>

        <div style="text-align: left; border-top: 1px dashed var(--color-border); border-bottom: 1px dashed var(--color-border); padding: 16px 0; margin-bottom: 16px;">
            @if ($order->restaurantTable)
                <div style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 10px;">{{ $order->restaurantTable->label }}</div>
            @endif

            @foreach ($order->items as $item)
                <div style="display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0;">
                    <span>{{ $item->quantity }} × {{ $item->product->name }}</span>
                    <span>{{ number_format($item->total, 0, ',', ' ') }}</span>
                </div>
            @endforeach
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 6px;">
            <span>Total</span>
            <strong>{{ number_format($order->total, 0, ',', ' ') }} FCFA</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 6px;">
            <span>Mode</span>
            <span>{{ $order->payment->methodLabel() }}</span>
        </div>
        @if ($order->payment->isMobile())
            <div style="display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 6px;">
                <span>{{ \App\Models\Payment::METHODS[$order->payment->payment_method] ?? 'Mobile Money' }}</span>
                <span>{{ number_format($order->payment->mobile_amount, 0, ',', ' ') }} FCFA</span>
            </div>
            @if ($order->payment->reference)
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; color: var(--color-text-secondary);">
                    <span>Référence</span>
                    <span>{{ $order->payment->reference }}</span>
                </div>
            @endif
        @endif
        <div style="display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 6px;">
            <span>Espèces reçues</span>
            <span>{{ number_format($order->payment->amount_received, 0, ',', ' ') }} FCFA</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 15px; margin-bottom: 20px; color: var(--color-accent); font-weight: 700;">
            <span>Monnaie rendue</span>
            <span>{{ number_format($order->payment->change_given, 0, ',', ' ') }} FCFA</span>
        </div>

        <a href="{{ route('tables.index') }}" class="btn btn-primary" style="width: 100%; justify-content: center;">
            Retour aux tables
        </a>
    </div>
</div>