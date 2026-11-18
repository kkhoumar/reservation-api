<div>
    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Super Admin
        </div>
        <h1>Nouveau maquis</h1>
    </div>

    @if ($created)
        {{-- Identifiants affichés une seule fois --}}
        <div class="card" style="max-width: 560px; border-left: 6px solid var(--color-primary-300);">
            <h3 style="margin-top: 0;">✅ {{ $created['restaurant_name'] }} est prêt</h3>
            <p style="font-size: 13px; color: var(--color-text-secondary); margin-top: 0;">
                Transmets ces identifiants au propriétaire. <strong>Le mot de passe ne sera plus affiché</strong> après avoir quitté cette page.
            </p>

            <div style="display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; font-size: 15px; margin: 16px 0;">
                <span style="color: var(--color-text-secondary);">Téléphone</span>
                <strong>{{ $created['phone'] }}</strong>
                <span style="color: var(--color-text-secondary);">Mot de passe provisoire</span>
                <strong style="font-family: monospace; font-size: 18px; letter-spacing: 0.05em;">{{ $created['password'] }}</strong>
                @if ($created['trial_ends_at'])
                    <span style="color: var(--color-text-secondary);">Fin de l'essai</span>
                    <strong>{{ $created['trial_ends_at'] }}</strong>
                @endif
            </div>

            <label style="font-size: 13px; font-weight: 600;">Message à envoyer</label>
            <textarea readonly rows="9" style="width: 100%; margin: 6px 0 12px 0; font-family: inherit; font-size: 13px; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--color-border);">{{ $whatsapp }}</textarea>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="https://wa.me/225{{ $created['phone'] }}?text={{ rawurlencode($whatsapp) }}" target="_blank" rel="noopener" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                    Envoyer par WhatsApp
                </a>
                <a href="{{ route('admin.restaurants') }}" class="btn btn-ghost" style="flex: 1; text-align: center; text-decoration: none;">
                    Retour à la liste
                </a>
            </div>
        </div>
    @else
        <form wire:submit="save" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; max-width: 900px; align-items: start;">
            <div class="card">
                <h3 style="margin-top: 0;">L'établissement</h3>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 600;">Nom</label>
                    <input type="text" wire:model="name" placeholder="Maquis Chez Tantie" style="width: 100%; margin-top: 6px;">
                    @error('name') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 600;">Type</label>
                    <select wire:model="type" style="width: 100%; margin-top: 6px;">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 600;">Téléphone du maquis</label>
                    <input type="text" wire:model="restaurantPhone" inputmode="tel" placeholder="07 XX XX XX XX" style="width: 100%; margin-top: 6px;">
                    @error('restaurantPhone') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 600;">Commune</label>
                        <input type="text" wire:model="commune" placeholder="Yopougon" style="width: 100%; margin-top: 6px;">
                        @error('commune') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 600;">Ville</label>
                        <input type="text" wire:model="city" style="width: 100%; margin-top: 6px;">
                        @error('city') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 600;">Mode de service</label>
                    <select wire:model="serviceMode" style="width: 100%; margin-top: 6px;">
                        @foreach ($modes as $value => $mode)
                            <option value="{{ $value }}">{{ $mode['label'] }}</option>
                        @endforeach
                    </select>
                    @error('serviceMode') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 600;">Moment du paiement</label>
                    <select wire:model="paymentTiming" style="width: 100%; margin-top: 6px;">
                        @foreach ($timings as $value => $timing)
                            <option value="{{ $value }}">{{ $timing['label'] }}</option>
                        @endforeach
                    </select>
                    @error('paymentTiming') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>

                <label style="display: flex; gap: 8px; align-items: center; font-size: 13px; font-weight: 600; margin-bottom: 14px; cursor: pointer;">
                    <input type="checkbox" wire:model="creditEnabled">
                    Crédit client activé
                </label>

                <div>
                    <label style="font-size: 13px; font-weight: 600;">Jours d'essai gratuit</label>
                    <input type="number" wire:model="trialDays" min="0" max="90" style="width: 100%; margin-top: 6px;">
                    <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">0 = abonnement actif tout de suite</div>
                    @error('trialDays') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top: 0;">Le propriétaire</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 600;">Prénom</label>
                        <input type="text" wire:model="ownerFirstName" style="width: 100%; margin-top: 6px;">
                        @error('ownerFirstName') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 600;">Nom</label>
                        <input type="text" wire:model="ownerLastName" style="width: 100%; margin-top: 6px;">
                        @error('ownerLastName') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 600;">Téléphone (sert à se connecter)</label>
                    <input type="text" wire:model="ownerPhone" inputmode="tel" placeholder="07 XX XX XX XX" style="width: 100%; margin-top: 6px;">
                    @error('ownerPhone') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>

                <p style="font-size: 13px; color: var(--color-text-secondary);">
                    Un mot de passe provisoire sera généré. Le propriétaire choisira le sien à sa première connexion.
                </p>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;" wire:loading.attr="disabled">Créer le maquis</button>
                    <a href="{{ route('admin.restaurants') }}" class="btn btn-ghost" style="flex: 1; text-align: center; text-decoration: none;">Annuler</a>
                </div>
            </div>
        </form>
    @endif
</div>
