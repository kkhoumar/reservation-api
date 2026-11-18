<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Mon maquis
        </div>
        <h1>Paramètres</h1>
    </div>

    <form wire:submit="save" style="max-width: 560px;">
        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Comment servez-vous vos clients ?</h3>

            @foreach ($modes as $value => $mode)
                <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid {{ $serviceMode === $value ? 'var(--color-primary)' : 'var(--color-border)' }}; border-radius: 10px; margin-bottom: 8px; cursor: pointer;">
                    <input type="radio" wire:model.live="serviceMode" value="{{ $value }}" style="margin-top: 3px;">
                    <span>
                        <strong>{{ $mode['label'] }}</strong>
                        <span style="display: block; font-size: 13px; color: var(--color-text-secondary);">{{ $mode['hint'] }}</span>
                    </span>
                </label>
            @endforeach
            @error('serviceMode') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
        </div>

        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Moment du paiement</h3>
            @foreach ($timings as $value => $timing)
                <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid {{ $paymentTiming === $value ? 'var(--color-primary)' : 'var(--color-border)' }}; border-radius: 10px; margin-bottom: 8px; cursor: pointer;">
                    <input type="radio" wire:model.live="paymentTiming" value="{{ $value }}" style="margin-top: 3px;">
                    <span>
                        <strong>{{ $timing['label'] }}</strong>
                        <span style="display: block; font-size: 13px; color: var(--color-text-secondary);">{{ $timing['hint'] }}</span>
                    </span>
                </label>
            @endforeach
            @error('paymentTiming') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
        </div>

        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Ardoise (crédit client)</h3>
            <label style="display: flex; gap: 12px; align-items: flex-start; cursor: pointer;">
                <input type="checkbox" wire:model="creditEnabled" style="margin-top: 3px;">
                <span>
                    <strong>Autoriser les clients à payer plus tard</strong>
                    <span style="display: block; font-size: 13px; color: var(--color-text-secondary);">
                        Les sommes dues sont notées sur l'ardoise du client, avec qui a autorisé le crédit.
                    </span>
                </span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>
