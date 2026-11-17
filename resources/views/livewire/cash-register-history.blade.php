<div>
    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Historique
        </div>
        <h1>Sessions de caisse</h1>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        @if ($registers->isEmpty())
            <p style="padding: 24px; color: var(--color-text-secondary); text-align: center;">
                Aucune session clôturée pour le moment.
            </p>
        @else
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--color-border); text-align: left;">
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Ouverte</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Clôturée</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Ouverte par</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Clôturée par</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600; text-align: right;">Montant compté</th>
                        <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600; text-align: right;">Écart</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registers as $register)
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 12px 20px;">{{ $register->opened_at->format('d/m H:i') }}</td>
                            <td style="padding: 12px 20px;">{{ $register->closed_at->format('d/m H:i') }}</td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $register->openedByUser->first_name ?? '—' }}</td>
                            <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $register->closedByUser->first_name ?? '—' }}</td>
                            <td style="padding: 12px 20px; text-align: right; font-weight: 600;">{{ number_format($register->counted_amount, 0, ',', ' ') }} FCFA</td>
                            <td style="padding: 12px 20px; text-align: right;">
                                @php $hasIssue = abs($register->discrepancy) >= 500; @endphp
                                <span style="font-weight: 700; color: {{ $hasIssue ? 'var(--color-danger)' : 'var(--color-primary-300)' }};">
                                    {{ $register->discrepancy > 0 ? '+' : '' }}{{ number_format($register->discrepancy, 0, ',', ' ') }}
                                    @if ($hasIssue) ⚠️ @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="padding: 16px 20px;">
                {{ $registers->links() }}
            </div>
        @endif
    </div>
</div>