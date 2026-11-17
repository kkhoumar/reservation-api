<div>
    <div class="auth-badge">ME</div>
    <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 4px 0;">Maquis Express</h1>
    <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0 0 24px 0;">
        Connectez-vous à votre établissement
    </p>

    @if (session('error'))
        <div class="error" style="margin: 0 0 16px 0;">{{ session('error') }}</div>
    @endif

    <form wire:submit="authenticate">
        <div style="margin-bottom: 16px;">
            <label>Téléphone</label>
            <input type="text" wire:model="phone" placeholder="+225 07 XX XX XX XX" autofocus>
            @error('phone') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom: 8px;">
            <label>Mot de passe</label>
            <input type="password" wire:model="password" placeholder="••••••••">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn-primary">Se connecter</button>
    </form>
</div>