<div>
    <div style="margin-bottom: 20px;">
        <a href="{{ route('admin.restaurants') }}" style="font-size: 13px; color: var(--color-text-secondary); text-decoration: none;">← Tous les maquis</a>
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin: 8px 0 4px 0;">
            Super Admin
        </div>
        <h1>Réglages de la plateforme</h1>
    </div>

    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card" style="max-width: 560px;">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
            <span style="width: 32px; height: 32px; border-radius: 10px; background: #25D366; color: #fff; display: inline-grid; place-items: center;">
                @include('partials.whatsapp-logo', ['size' => 18])
            </span>
            WhatsApp du service client
        </h3>
        <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0 0 14px 0;">
            Le bouton vert en bas à droite (page d'accueil, connexion, et écrans du patron et du gérant) ouvre une conversation WhatsApp avec ce numéro.
        </p>

        <form wire:submit="save">
            <label style="font-size: 13px; font-weight: 600; display: block;">Numéro WhatsApp</label>
            <input type="tel" inputmode="tel" wire:model="whatsapp" placeholder="07 00 00 00 00" style="width: 100%; margin-top: 4px; font-size: 18px;">
            @error('whatsapp') <div style="color: var(--color-danger); font-size: 12px; margin-top: 4px;">{{ $message }}</div> @enderror

            <div style="display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                @if ($testUrl)
                    <a href="{{ $testUrl }}" target="_blank" rel="noopener" class="btn btn-ghost" style="text-decoration: none;">Tester le lien</a>
                @endif
            </div>
        </form>

        <div style="font-size: 13px; color: var(--color-text-secondary); margin-top: 14px;">
            @if ($activeNumber)
                Numéro utilisé en ce moment : <strong>{{ trim(chunk_split($activeNumber, 2, ' ')) }}</strong>
            @else
                Aucun numéro : le bouton WhatsApp est caché.
            @endif
        </div>
    </div>
</div>
