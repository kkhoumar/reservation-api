<div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Point de caisse
            </div>
            <h1>Ventes du jour</h1>
        </div>

        <div class="card" style="padding: 12px 20px; text-align: right;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Total encaissé aujourd'hui</div>
            <div style="font-size: 24px; font-weight: 800; color: var(--color-primary);">
                {{ number_format($total, 0, ',', ' ') }} FCFA
            </div>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        @if ($payments->isEmpty())
            <p style="padding: 24px; color: var(--color-text-secondary); text-align: center;">
                Aucune vente encaissée aujourd'hui pour le moment.
            </p>
        @else
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--color-border); text-align: left;">
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Commande</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Table</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Encaissé par</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Heure</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600; text-align: right;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 12px 20px; font-weight: 600;">{{ $payment->order->order_number }}</td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">
                                {{ $payment->order->restaurantTable->label ?? 'À emporter' }}
                            </td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $payment->cashier->first_name }}</td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $payment->paid_at->format('H:i') }}</td>
                            <td style="padding: 12px 20px; text-align: right; font-weight: 700;">
                                {{ number_format($payment->order->total, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>