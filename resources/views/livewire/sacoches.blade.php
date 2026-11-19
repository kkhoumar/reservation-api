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
        <h1>Sacoches des serveuses</h1>
        <p style="color: var(--color-text-secondary); font-size: 14px; margin: 6px 0 0 0;">
            L'argent encaissé par une serveuse reste dans sa sacoche jusqu'à ce qu'elle le verse à la caisse.
        </p>
    </div>

    @if (! $register)
        <div class="card" style="border-left: 6px solid var(--color-accent); margin-bottom: 16px; font-size: 14px;">
            La caisse est fermée : <a href="{{ route('cash-register.manage') }}" style="color: var(--color-accent); font-weight: 600;">ouvrez-la</a> pour recevoir les versements.
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; gap: 12px; flex-wrap: wrap;">
                <h3 style="margin: 0;">Ce que chacune doit verser</h3>
                <div style="font-size: 14px;">
                    Total dehors :
                    <strong style="color: {{ $totalOutstanding > 0 ? 'var(--color-accent)' : 'var(--color-primary-300)' }};">
                        {{ number_format($totalOutstanding, 0, ',', ' ') }} FCFA
                    </strong>
                </div>
            </div>

            @if ($servers->isEmpty())
                <p style="color: var(--color-text-secondary); font-size: 14px;">Aucune serveuse dans ce maquis.</p>
            @else
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="text-align: left; color: var(--color-text-secondary); font-size: 13px;">
                            <th style="padding: 8px 0; font-weight: 600;">Serveuse</th>
                            <th style="padding: 8px 0; font-weight: 600; text-align: right;">Encaissé</th>
                            <th style="padding: 8px 0; font-weight: 600; text-align: right;">Versé</th>
                            <th style="padding: 8px 0; font-weight: 600; text-align: right;">Dans la sacoche</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($servers as $server)
                            <tr style="border-top: 1px solid var(--color-border);">
                                <td style="padding: 10px 0;">
                                    {{ $server->first_name }} {{ $server->last_name }}
                                    @if ($server->status !== 'active')
                                        <span style="font-size: 11px; color: var(--color-danger);">(désactivée)</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 0; text-align: right;">{{ number_format($server->collected, 0, ',', ' ') }}</td>
                                <td style="padding: 10px 0; text-align: right;">{{ number_format($server->remitted, 0, ',', ' ') }}</td>
                                <td style="padding: 10px 0; text-align: right; font-weight: 700; color: {{ $server->balance > 0 ? 'var(--color-accent)' : 'var(--color-primary-300)' }};">
                                    {{ number_format($server->balance, 0, ',', ' ') }} FCFA
                                </td>
                                <td style="padding: 10px 0 10px 12px; text-align: right;">
                                    @if ($server->balance > 0 && $register)
                                        <button wire:click="startRemittance({{ $server->id }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                                            Recevoir
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="card">
            @if ($selected)
                <h3 style="margin-top: 0;">Versement de {{ $selected->first_name }}</h3>
                <p style="font-size: 13px; color: var(--color-text-secondary);">
                    Dans sa sacoche : <strong>{{ number_format($selected->balance, 0, ',', ' ') }} FCFA</strong>.
                    Comptez l'argent qu'elle vous remet.
                </p>

                <label style="font-size: 13px; font-weight: 600;">Montant reçu (FCFA)</label>
                <x-money-input model="amount" style="width: 100%; margin: 6px 0 8px 0; font-size: 18px;" />
                @error('amount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror

                <label style="font-size: 13px; font-weight: 600;">Note (facultatif)</label>
                <input type="text" wire:model="note" maxlength="255" placeholder="Ex : versement de mi-journée" style="width: 100%; margin: 6px 0 16px 0;">
                @error('note') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror

                <button wire:click="confirmRemittance" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Confirmer le versement
                </button>
                <button wire:click="cancel" class="btn btn-ghost" style="width: 100%; margin-top: 8px; justify-content: center;">
                    Annuler
                </button>
            @else
                <h3 style="margin-top: 0;">Versements de la session</h3>
                @forelse ($recent as $remittance)
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--color-border); font-size: 13px;">
                        <span>
                            {{ $remittance->created_at->format('H:i') }} · {{ $remittance->server->first_name }}
                            <span style="color: var(--color-text-secondary);">→ {{ $remittance->receiver->first_name }}</span>
                        </span>
                        <strong>{{ number_format($remittance->amount, 0, ',', ' ') }}</strong>
                    </div>
                @empty
                    <p style="font-size: 13px; color: var(--color-text-secondary);">Aucun versement depuis l'ouverture de la caisse.</p>
                @endforelse
            @endif
        </div>
    </div>
</div>
