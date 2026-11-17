<div>
    <div class="auth-badge">ME</div>
    <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 4px 0;">Choisis ton mot de passe</h1>
    <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0 0 24px 0;">
        @if ($forced)
            Bienvenue {{ auth()->user()->first_name }} ! Pour ta sécurité, remplace le mot de passe provisoire par un mot de passe que toi seul connais.
        @else
            Modifie ton mot de passe.
        @endif
    </p>

    <form wire:submit="save">
        <div style="margin-bottom: 16px;">
            <label>Mot de passe actuel{{ $forced ? ' (provisoire)' : '' }}</label>
            <input type="password" wire:model="currentPassword" autocomplete="current-password" autofocus>
            @error('currentPassword') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom: 16px;">
            <label>Nouveau mot de passe (8 caractères minimum)</label>
            <input type="password" wire:model="password" autocomplete="new-password">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom: 8px;">
            <label>Confirme le nouveau mot de passe</label>
            <input type="password" wire:model="passwordConfirmation" autocomplete="new-password">
        </div>

        <button type="submit" class="btn-primary">Enregistrer</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 16px; text-align: center;">
        @csrf
        <button type="submit" style="background: none; border: none; color: var(--color-text-secondary); font-size: 13px; cursor: pointer; text-decoration: underline;">Se déconnecter</button>
    </form>
</div>
