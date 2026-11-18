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

    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Encaissement
        </div>
        <h1>Caisse</h1>
    </div>

    @if (!$register)
        <div class="card" style="max-width: 420px;">
            <h3 style="margin-top: 0;">Ouvrir la caisse</h3>
            <p style="color: var(--color-text-secondary); font-size: 13px;">
                Saisis le fonds de caisse initial (montant en espèces présent au démarrage).
            </p>

            <label style="font-size: 13px; font-weight: 600;">Fonds initial (FCFA)</label>
            <input type="number" wire:model="openingAmount" style="width: 100%; margin: 6px 0 16px 0; font-size: 18px;">
            @error('openingAmount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 12px;">{{ $message }}</div> @enderror

            <button wire:click="openRegister" class="btn btn-primary" style="width: 100%;">
                Ouvrir la caisse
            </button>
        </div>
    @else
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">

            <div class="card">
                <h3 style="margin-top: 0;">Session en cours</h3>
                <p style="font-size: 13px; color: var(--color-text-secondary);">
                    Ouverte le {{ $register->opened_at->format('d/m/Y à H:i') }} par {{ $register->openedByUser->first_name }}
                </p>

                {{-- Décomposition détaillée --}}
                <div style="margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
                        <span>Fonds initial</span>
                        <strong>{{ number_format($breakdown['opening_amount'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
                        <span>+ Encaissé à la caisse ({{ $breakdown['payments_count'] }} paiement{{ $breakdown['payments_count'] > 1 ? 's' : '' }})</span>
                        <strong style="color: var(--color-primary-300);">+{{ number_format($breakdown['received'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
                        <span>- Monnaie rendue</span>
                        <strong style="color: var(--color-danger);">-{{ number_format($breakdown['change_given'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
                        <span>+ Versements des serveuses ({{ $breakdown['remittances_count'] }})</span>
                        <strong style="color: var(--color-primary-300);">+{{ number_format($breakdown['remittances_total'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
                        <span>+ Remboursements d'ardoise ({{ $breakdown['repayments_count'] }})</span>
                        <strong style="color: var(--color-primary-300);">+{{ number_format($breakdown['repayments_total'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
                        <span>- Dépenses ({{ $breakdown['expenses_count'] }})</span>
                        <strong style="color: var(--color-danger);">-{{ number_format($breakdown['expenses_total'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 12px 0 0 0; font-size: 16px;">
                        <span style="font-weight: 700;">= Solde théorique</span>
                        <strong style="color: var(--color-primary); font-size: 18px;">{{ number_format($breakdown['theoretical'], 0, ',', ' ') }} FCFA</strong>
                    </div>
                </div>

                @if ($history->isNotEmpty())
                    <h3 style="margin-top: 28px;">Sessions précédentes</h3>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px;">
                        <thead>
                            <tr style="text-align: left; color: var(--color-text-secondary);">
                                <th style="padding: 6px 0; font-weight: 600;">Clôturée le</th>
                                <th style="padding: 6px 0; font-weight: 600;">Par</th>
                                <th style="padding: 6px 0; font-weight: 600; text-align: right;">Écart</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $past)
                                <tr style="border-top: 1px solid var(--color-border);">
                                    <td style="padding: 8px 0;">{{ $past->closed_at->format('d/m H:i') }}</td>
                                    <td style="padding: 8px 0;">{{ $past->closedByUser->first_name ?? '—' }}</td>
                                    <td style="padding: 8px 0; text-align: right; color: {{ $past->discrepancy == 0 ? 'var(--color-primary-300)' : 'var(--color-danger)' }}; font-weight: 600;">
                                        {{ $past->discrepancy > 0 ? '+' : '' }}{{ number_format($past->discrepancy, 0, ',', ' ') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="card">
                @if ($outstanding->isNotEmpty())
                    <div style="border-left: 4px solid var(--color-accent); background: var(--color-bg); border-radius: 8px; padding: 10px 12px; margin-bottom: 16px; font-size: 13px;">
                        <strong>Sacoches non vidées</strong>
                        @foreach ($outstanding as $server)
                            <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                                <span>{{ $server->first_name }}</span>
                                <strong>{{ number_format($server->balance, 0, ',', ' ') }} FCFA</strong>
                            </div>
                        @endforeach
                        <a href="{{ route('sacoches') }}" style="display: inline-block; margin-top: 8px; color: var(--color-accent); font-weight: 600;">Recevoir les versements →</a>
                    </div>
                @endif
                @if (!$showCloseForm)
                    <h3 style="margin-top: 0;">Clôture</h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 16px;">
                        Compte le montant réellement présent en caisse pour clôturer la session.
                    </p>
                    <button wire:click="$set('showCloseForm', true)" class="btn btn-danger" style="width: 100%;">
                        Clôturer la caisse
                    </button>
                @else
                    <h3 style="margin-top: 0;">Montant compté</h3>
                    <label style="font-size: 13px; font-weight: 600;">Montant réel en caisse (FCFA)</label>
                    <input type="number" wire:model.live="countedAmount" style="width: 100%; margin: 6px 0 16px 0; font-size: 18px;">
                    @error('countedAmount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 12px;">{{ $message }}</div> @enderror

                    @if ($countedAmount !== null)
                        @php $diff = $countedAmount - $breakdown['theoretical']; @endphp
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 16px; padding: 10px; background: var(--color-bg); border-radius: 8px;">
                            <span>Écart</span>
                            <strong style="color: {{ $diff == 0 ? 'var(--color-primary-300)' : 'var(--color-danger)' }};">
                                {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0, ',', ' ') }} FCFA
                            </strong>
                        </div>

                        @if (abs($diff) >= $threshold)
                            <label style="font-size: 13px; font-weight: 600;">Raison de l'écart (obligatoire au-delà de {{ number_format($threshold, 0, ',', ' ') }} FCFA)</label>
                            <textarea wire:model="closingNote" rows="3" style="width: 100%; margin: 6px 0 12px 0; font-family: inherit; font-size: 14px; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--color-border);" placeholder="Ex : erreur de rendu monnaie sur commande CMD-00012"></textarea>
                            @error('closingNote') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 12px;">{{ $message }}</div> @enderror
                        @endif
                    @endif

                    <button wire:click="confirmClose" class="btn btn-danger" style="width: 100%;">
                        Confirmer la clôture
                    </button>
                    <button wire:click="$set('showCloseForm', false)" class="btn btn-ghost" style="width: 100%; margin-top: 8px;">
                        Annuler
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>