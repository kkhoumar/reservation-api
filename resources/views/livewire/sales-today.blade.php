<div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Point de caisse
            </div>
            <h1>Ventes du jour</h1>
        </div>

        <div class="card" style="padding: 12px 20px; text-align: right;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Total vendu aujourd'hui</div>
            <div style="font-size: 24px; font-weight: 800; color: var(--color-primary);">
                {{ number_format($summary->total(), 0, ',', ' ') }} FCFA
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px;">
        <div class="card" style="padding: 14px 16px;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Espèces</div>
            <div style="font-size: 18px; font-weight: 700;">{{ number_format($summary->cash, 0, ',', ' ') }}</div>
        </div>
        @foreach (\App\Models\Payment::MOBILE_METHODS as $method)
            @if (($summary->mobile[$method] ?? 0) > 0)
                <div class="card" style="padding: 14px 16px;">
                    <div style="font-size: 12px; color: var(--color-text-secondary);">{{ \App\Models\Payment::METHODS[$method] }}</div>
                    <div style="font-size: 18px; font-weight: 700;">{{ number_format($summary->mobile[$method], 0, ',', ' ') }}</div>
                </div>
            @endif
        @endforeach
        @if ($summary->credit > 0)
            <div class="card" style="padding: 14px 16px;">
                <div style="font-size: 12px; color: var(--color-text-secondary);">À crédit</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--color-accent);">{{ number_format($summary->credit, 0, ',', ' ') }}</div>
            </div>
        @endif
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        @if ($sales->isEmpty())
            <p style="padding: 24px; color: var(--color-text-secondary); text-align: center;">
                Aucune vente réglée aujourd'hui pour le moment.
            </p>
        @else
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--color-border); text-align: left;">
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Commande</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Table</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Réglé en</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Par</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Heure</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600; text-align: right;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 12px 20px; font-weight: 600;">{{ $sale->order?->order_number ?? '—' }}</td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">
                                {{ $sale->order?->restaurantTable?->label ?? 'À emporter' }}
                            </td>
                            <td style="padding: 12px 20px; {{ $sale->credit ? 'color: var(--color-accent); font-weight: 600;' : '' }}">
                                {{ $sale->mode }}
                                @if ($sale->detail)
                                    <span style="display: block; font-size: 12px; font-weight: 400; color: var(--color-text-secondary);">{{ $sale->detail }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $sale->by ?? '—' }}</td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $sale->at->format('H:i') }}</td>
                            <td style="padding: 12px 20px; text-align: right; font-weight: 700;">
                                {{ number_format($sale->amount, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
