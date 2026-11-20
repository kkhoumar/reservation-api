{{--
    Caisse fermée : on ne peut pas encaisser. La caissière (ou le patron)
    l'ouvre ici, sans quitter l'encaissement. Les autres doivent demander.
--}}
<div style="background: var(--color-warning-bg); border: 1px solid #F0D9A8; border-radius: 14px; padding: 14px 16px;">
    <div style="display: flex; gap: 10px; align-items: flex-start;">
        <x-icon name="lock" size="20" style="color: #9A5B0C; margin-top: 1px;" />
        <div style="min-width: 0; flex: 1;">
            <strong style="display: block; font-size: 15px; color: #6B4A12;">La caisse est fermée</strong>
            <span style="display: block; font-size: 13.5px; color: #6B4A12; line-height: 1.45; margin-top: 2px;">
                Pour encaisser, la caisse doit être ouverte. Sinon l'argent ne serait compté dans aucune caisse.
            </span>

            @if (\App\Models\CashRegister::canOpen(auth()->user()))
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-top: 12px;">
                    <div style="flex: 1 1 150px; min-width: 0;">
                        <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 5px; color: #6B4A12;">Fonds de départ (FCFA)</label>
                        <x-money-input model="openingAmount" :empty="0" placeholder="0" style="width: 100%; font-size: 17px;" />
                    </div>
                    <button wire:click="openRegisterHere" class="btn btn-primary" style="flex: 1 1 180px; justify-content: center;">
                        Ouvrir la caisse
                    </button>
                </div>
                @error('openingAmount') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
            @else
                <span style="display: block; font-size: 13.5px; font-weight: 700; color: #6B4A12; margin-top: 8px;">Demandez à la caissière ou au patron d'ouvrir la caisse.</span>
            @endif
        </div>
    </div>
</div>
