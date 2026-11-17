<div>
    <div class="auth-badge">ME</div>
    <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 4px 0;">Créer votre établissement</h1>
    <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0 0 20px 0;">
        14 jours d'essai gratuit, sans engagement.
    </p>

   

    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
        <div style="flex: 1; height: 1px; background: var(--color-border);"></div>
        <span style="font-size: 12px; color: var(--color-text-secondary);">ou</span>
        <div style="flex: 1; height: 1px; background: var(--color-border);"></div>
    </div>

    <form wire:submit="register">
        <div style="margin-bottom: 14px;">
            <label>Nom de l'établissement</label>
            <input type="text" wire:model="restaurantName" placeholder="Maquis Chez Koffi">
            @error('restaurantName') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
            <div>
                <label>Prénom</label>
                <input type="text" wire:model="firstName" placeholder="Koffi">
                @error('firstName') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Nom</label>
                <input type="text" wire:model="lastName" placeholder="Yao">
                @error('lastName') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="margin-bottom: 14px;">
            <label>Téléphone</label>
            <input type="text" wire:model="phone" placeholder="+225 07 XX XX XX XX">
            @error('phone') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom: 14px;">
            <label>Mot de passe</label>
            <input type="password" wire:model="password" placeholder="••••••••">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom: 8px;">
            <label>Confirmer le mot de passe</label>
            <input type="password" wire:model="passwordConfirmation" placeholder="••••••••">
        </div>

        <button type="submit" class="btn-primary">Créer mon établissement</button>
    </form>

    <p style="text-align: center; font-size: 13px; color: var(--color-text-secondary); margin-top: 16px;">
        Déjà un compte ? <a href="{{ route('login') }}" style="color: var(--color-accent); font-weight: 600;">Se connecter</a>
    </p>
</div>