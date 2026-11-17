<div>
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
        {{-- Aucune caisse ouverte : formulaire d'ouverture --}}
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
        {{-- Caisse ouverte : vue en cours --}}
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">

            <div class="card">
                <h3 style="margin-top: 0;">Session en cours</h3>
                <p style="font-size: 13px; color: var(--color-text-secondary);">
                    Ouverte le {{ $register->opened_at->format('d/m/Y à H:i') }} par {{ $register->openedByUser->first_name }}
                </p>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 16px;">
                    <div>
                        <div style="font-size: 12px; color: var(--color-text-secondary);">Fonds initial</div>
                        <div style="font-size: 20px; font-weight: 700;">{{ number_format($register->opening_amount, 0, ',', ' ') }} FCFA</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--color-text-secondary);">Solde théorique actuel</div>
                        <div style="font-size: 20px; font-weight: 700; color: var(--color-primary);">{{ number_format($theoretical, 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>

                @if ($expenses->isNotEmpty())
                    <h3 style="margin-top: 24px;">Dépenses de la session</h3>
                    @foreach ($expenses as $expense)
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--color-border); font-size: 13px;">
                            <span>{{ $expense->description ?: $expense->category }}</span>
                            <span style="color: var(--color-danger); font-weight: 600;">-{{ number_format($expense->amount, 0, ',', ' ') }} FCFA</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="card">
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
                    <input type="number" wire:model="countedAmount" style="width: 100%; margin: 6px 0 16px 0; font-size: 18px;">
                    @error('countedAmount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 12px;">{{ $message }}</div> @enderror

                    @if ($countedAmount !== null)
                        @php $diff = $countedAmount - $theoretical; @endphp
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 16px; padding: 10px; background: var(--color-bg); border-radius: 8px;">
                            <span>Écart</span>
                            <strong style="color: {{ $diff == 0 ? 'var(--color-primary-300)' : 'var(--color-danger)' }};">
                                {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0, ',', ' ') }} FCFA
                            </strong>
                        </div>
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