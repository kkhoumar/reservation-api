<div style="display: flex; justify-content: center; align-items: flex-start; gap: 16px; flex-wrap: wrap; padding-top: 20px;">
    <div class="card" style="max-width: 460px; width: 100%; text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(101,185,110,0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0B5D3B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>

        <h1 style="font-size: 20px; margin-bottom: 4px;">Caisse clôturée</h1>
        <p style="color: var(--color-text-secondary); font-size: 13px; margin-bottom: 24px;">
            Session du {{ $register->opened_at->format('d/m/Y H:i') }} au {{ $register->closed_at->format('H:i') }}
        </p>

        <div style="text-align: left; border-top: 1px dashed var(--color-border); border-bottom: 1px dashed var(--color-border); padding: 16px 0; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0;">
                <span>Ouverte par</span>
                <span>{{ $register->openedByUser->first_name }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0;">
                <span>Clôturée par</span>
                <span>{{ $register->closedByUser->first_name ?? '—' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0;">
                <span>Solde théorique</span>
                <span>{{ number_format($register->theoretical_amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0;">
                <span>Montant compté</span>
                <span>{{ number_format($register->counted_amount, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 17px; margin-bottom: 4px;">
            <span style="font-weight: 700;">Écart</span>
            <strong style="color: {{ $register->discrepancy == 0 ? 'var(--color-primary-300)' : 'var(--color-danger)' }};">
                {{ $register->discrepancy > 0 ? '+' : '' }}{{ number_format($register->discrepancy, 0, ',', ' ') }} FCFA
            </strong>
        </div>

        @if ($register->closing_note)
            <div style="text-align: left; background: var(--color-bg); border-radius: 8px; padding: 12px; margin-top: 16px; font-size: 13px;">
                <strong>Note :</strong> {{ $register->closing_note }}
            </div>
        @endif

        <a href="{{ route('tables.index') }}" class="btn btn-ghost" style="width: 100%; justify-content: center; margin-top: 20px;">
            Retour aux tables
        </a>
    </div>

    <div class="card" style="max-width: 460px; width: 100%;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Rapport du soir
        </div>
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
            <h3 style="margin: 0;">Ventes de la session</h3>
            <strong style="font-size: 20px; color: var(--color-primary);">{{ number_format($report->totalSales(), 0, ',', ' ') }} FCFA</strong>
        </div>

        <div id="report-text" style="white-space: pre-wrap; font-size: 13px; line-height: 1.55; background: var(--color-bg); border: 1px solid var(--color-border); border-radius: 10px; padding: 14px; max-height: 360px; overflow-y: auto;">{{ $reportText }}</div>

        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 14px;">
            @if ($ownerUrl && ! $isOwner)
                <a href="{{ $ownerUrl }}" target="_blank" rel="noopener" class="btn btn-primary" style="justify-content: center; text-decoration: none;">
                    Envoyer au patron sur WhatsApp
                </a>
            @endif
            <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="btn {{ $ownerUrl && ! $isOwner ? 'btn-ghost' : 'btn-primary' }}" style="justify-content: center; text-decoration: none;">
                Partager sur WhatsApp
            </a>
            <button type="button" class="btn btn-ghost" style="justify-content: center;"
                    onclick="navigator.clipboard.writeText(document.getElementById('report-text').innerText).then(() => { this.innerText = 'Copié ✓'; setTimeout(() => this.innerText = 'Copier le texte', 2000); })">
                Copier le texte
            </button>
        </div>
    </div>
</div>