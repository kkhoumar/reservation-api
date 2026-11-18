<div>
    <div class="auth-badge">ME</div>
    <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 8px 0;">
        {{ $isUpdate ? 'Nos conditions ont changé' : 'Bienvenue sur Maquis Express' }}
    </h1>
    <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0 0 16px 0; line-height: 1.5;">
        @if ($isUpdate)
            Nous avons mis à jour nos conditions. Merci de les relire et de les accepter pour continuer.
        @else
            Avant de commencer, merci de lire et d'accepter les règles d'utilisation de l'application.
        @endif
    </p>

    <div style="background: #FBF6EC; border-radius: 10px; padding: 12px 14px; font-size: 14px; line-height: 1.7; margin-bottom: 16px;">
        📄 <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Conditions d'utilisation</a><br>
        🔒 <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Politique de confidentialité</a>
    </div>

    <form wire:submit="accept">
        <label style="display: flex; gap: 10px; align-items: flex-start; font-weight: 500; cursor: pointer; line-height: 1.4;">
            <input type="checkbox" wire:model="accepted" style="width: auto; margin-top: 3px;">
            <span>J'ai lu et j'accepte les conditions d'utilisation et la politique de confidentialité de Maquis Express, pour mon établissement.</span>
        </label>
        @error('accepted') <div class="error">{{ $message }}</div> @enderror

        <button type="submit" class="btn-primary" style="margin-top: 16px;">Accepter et continuer</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 10px; text-align: center;">
        @csrf
        <button type="submit" style="background: none; border: none; color: var(--color-text-secondary); font-size: 13px; cursor: pointer; font-family: inherit;">Se déconnecter</button>
    </form>
</div>
