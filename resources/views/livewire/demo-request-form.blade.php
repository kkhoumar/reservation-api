<div>
    <div class="auth-badge">ME</div>

    @if ($sent)
        <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 8px 0;">Merci, c'est noté !</h1>
        <p style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.6; margin: 0 0 24px 0;">
            Nous vous appelons très vite pour vous présenter Maquis Express et installer votre établissement.
            Les 14 premiers jours sont gratuits.
        </p>
        <a href="{{ route('landing') }}" class="btn-primary" style="display: block; text-align: center; text-decoration: none;">Retour à l'accueil</a>
    @else
        <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 4px 0;">Demander une démo</h1>
        <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0 0 20px 0;">
            Laissez vos coordonnées : nous vous appelons et installons votre maquis avec vous. 14 jours d'essai gratuit.
        </p>

        <form wire:submit="submit">
            <div style="margin-bottom: 14px;">
                <label>Votre nom</label>
                <input type="text" wire:model="contactName" placeholder="Koffi Yao" autocomplete="name">
                @error('contactName') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom: 14px;">
                <label>Nom de l'établissement</label>
                <input type="text" wire:model="restaurantName" placeholder="Maquis Chez Koffi">
                @error('restaurantName') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    <label>Téléphone / WhatsApp</label>
                    <input type="text" wire:model="phone" inputmode="tel" placeholder="07 XX XX XX XX" autocomplete="tel">
                    @error('phone') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Commune</label>
                    <input type="text" wire:model="commune" placeholder="Yopougon">
                    @error('commune') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label>Message (facultatif)</label>
                <input type="text" wire:model="message" placeholder="Ex : 12 tables, 4 serveurs">
                @error('message') <div class="error">{{ $message }}</div> @enderror
            </div>

            {{-- Piège à robots : invisible pour les humains --}}
            <div style="position: absolute; left: -10000px;" aria-hidden="true">
                <label>Site web</label>
                <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
            </div>

            <button type="submit" class="btn-primary" wire:loading.attr="disabled">Être rappelé</button>
            <p style="font-size: 12px; color: var(--color-text-secondary); margin: 10px 0 0 0; line-height: 1.4;">
                Vos informations servent uniquement à vous rappeler.
                Voir notre <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">politique de confidentialité</a>.
            </p>
        </form>

        <p style="text-align: center; font-size: 13px; color: var(--color-text-secondary); margin-top: 16px;">
            Déjà client ? <a href="{{ route('login') }}" style="color: var(--color-accent); font-weight: 600;">Se connecter</a>
        </p>
    @endif
</div>
