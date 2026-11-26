<div x-data="{ show: false, help: false }">
    <h1 style="font-size: 28px; font-weight: 800; margin: 0 0 6px 0;">Bon retour</h1>
    <p style="font-size: 15px; color: var(--color-text-secondary); margin: 0 0 28px 0;">
        Connectez-vous pour accéder à votre établissement.
    </p>

    @if (session('error'))
        <div class="alert">{{ session('error') }}</div>
    @endif

    <form wire:submit="authenticate" novalidate>
        <div style="margin-bottom: 18px;">
            <label for="phone">Numéro de téléphone</label>
            <input id="phone" type="tel" wire:model="phone" placeholder="07 XX XX XX XX" inputmode="tel" autocomplete="username" autofocus>
            @error('phone') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <label for="password">Mot de passe</label>
                <button type="button" @click="help = !help" style="background: none; border: none; padding: 0; color: var(--color-accent); font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;">Mot de passe oublié ?</button>
            </div>
            <div style="position: relative;">
                <input id="password" :type="show ? 'text' : 'password'" type="password" wire:model="password" placeholder="Votre mot de passe" autocomplete="current-password" style="padding-right: 48px;">
                <button type="button" @click="show = !show" :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        style="position: absolute; right: 6px; top: 50%; transform: translateY(calc(-50% + 3px)); width: 38px; height: 38px; border: none; background: none; cursor: pointer; color: var(--color-text-secondary); display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <svg x-show="!show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="show" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22"/></svg>
                </button>
            </div>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div x-show="help" x-cloak x-transition style="background: var(--color-mint); border-radius: 12px; padding: 12px 14px; font-size: 13px; line-height: 1.55; margin-bottom: 14px;">
            <strong>Vous êtes serveuse, caissière ou gérante ?</strong> Demandez à votre patron (ou à la gérante) de vous donner un nouveau mot de passe depuis <em>Utilisateurs</em>.<br>
            <strong>Vous êtes le patron ?</strong> Écrivez-nous sur WhatsApp <strong>depuis le numéro de votre compte</strong> : on vous envoie un nouveau mot de passe.
            @if ($supportUrl)
                <a :href="@js($supportUrl) + encodeURIComponent(@js($supportText) + (($wire.phone || '').trim() || '…'))" target="_blank" rel="noopener"
                   style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 10px; padding: 11px 14px; border-radius: 10px; background: #1FA855; color: #fff; font-weight: 700; text-decoration: none;">
                    Écrire sur WhatsApp
                </a>
            @endif
        </div>

        <label style="display: flex; gap: 10px; align-items: center; font-weight: 500; font-size: 14px; cursor: pointer; margin: 4px 0 2px;">
            <input type="checkbox" wire:model="remember" id="remember"> Rester connecté sur cet appareil
        </label>
        <div style="font-size: 12px; color: var(--color-text-secondary); margin: 0 0 18px 28px;">Décochez si ce téléphone est partagé par plusieurs personnes.</div>
        <script>
            // Application installée sur le téléphone : on reste connecté (comme WhatsApp ou Wave).
            // Sur le site dans le navigateur, la case reste décochée (ordinateur ou téléphone emprunté).
            document.addEventListener('livewire:initialized', function () {
                var installed = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
                var box = document.getElementById('remember');
                if (!installed || !box || box.checked) return;
                box.checked = true;
                box.dispatchEvent(new Event('input', { bubbles: true }));
                box.dispatchEvent(new Event('change', { bubbles: true }));
            });
        </script>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="authenticate">
            <span wire:loading.remove wire:target="authenticate">Se connecter</span>
            <span wire:loading wire:target="authenticate">Connexion…</span>
        </button>
    </form>

    <div style="margin-top: 28px; padding-top: 22px; border-top: 1px solid var(--color-border); text-align: center; font-size: 14px; color: var(--color-text-secondary);">
        Pas encore client ? <a href="{{ route('demo.request') }}" style="font-weight: 700; text-decoration: none;">Demander une démo gratuite →</a>
    </div>

    @include('partials.pwa-install', ['variant' => 'light'])

    <style>[x-cloak] { display: none !important; }</style>
</div>
