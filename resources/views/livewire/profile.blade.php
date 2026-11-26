<div class="sc pf">
    @include('partials.stock-count-styles')
    <style>
        .pf { max-width: 760px; }
        .pf-hero { display: flex; gap: 20px; align-items: center; background: linear-gradient(135deg, var(--color-primary), var(--color-primary-700)); color: #fff; border-radius: 20px; padding: 22px; margin: 18px 0; box-shadow: 0 14px 32px -14px rgba(11,77,60,.55); }
        .pf-photo { position: relative; flex-shrink: 0; }
        .pf-photo .me-avatar { border: 3px solid rgba(255,255,255,.85); box-shadow: 0 6px 16px rgba(0,0,0,.18); }
        .pf-photo .cam { position: absolute; right: -2px; bottom: -2px; width: 34px; height: 34px; border-radius: 50%; background: var(--color-accent); color: #fff; border: 3px solid #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; }
        .pf-photo .busy { position: absolute; inset: 0; border-radius: 50%; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
        .pf-name { font-size: 24px; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; }
        .pf-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .pf-tags span { font-size: 12.5px; font-weight: 700; background: rgba(255,255,255,.16); padding: 4px 10px; border-radius: 999px; }
        .pf-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .pf-actions button { border: 1px solid rgba(255,255,255,.4); background: rgba(255,255,255,.12); color: #fff; font: inherit; font-size: 13.5px; font-weight: 700; padding: 8px 14px; border-radius: 10px; cursor: pointer; min-height: 40px; }
        .pf-actions button.light { background: #fff; color: var(--color-primary); border-color: #fff; }
        .pf-err { background: rgba(255,255,255,.95); color: var(--color-danger); border-radius: 10px; padding: 8px 12px; font-size: 13.5px; font-weight: 600; margin-top: 10px; }
        .pf .sc-card { margin-bottom: 16px; padding: 20px; }
        .pf .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .pf .field { margin-bottom: 14px; }
        .pf .field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .pf .field input { width: 100%; }
        .pf .help { font-size: 12.5px; color: var(--color-text-secondary); margin-top: 5px; line-height: 1.45; }
        .pf .err { color: var(--color-danger); font-size: 13px; margin-top: 5px; }
        .pf .ok { display: flex; gap: 8px; align-items: center; background: var(--color-success-bg); color: var(--color-success); font-weight: 700; font-size: 14px; border-radius: 12px; padding: 10px 14px; margin-bottom: 14px; }
        @media (max-width: 560px) {
            .pf-hero { flex-direction: column; text-align: center; padding: 22px 16px; }
            .pf-tags, .pf-actions { justify-content: center; }
            .pf .grid2 { grid-template-columns: 1fr; gap: 0; }
            .pf .sc-card { padding: 16px; }
            .pf .sc-card .btn { width: 100%; justify-content: center; }
        }
    </style>

    <div class="page-head">
        <div class="eyebrow">Mon compte</div>
        <h1>Mon profil</h1>
    </div>

    {{-- ================= Photo et identité ================= --}}
    <div class="pf-hero" x-data="avatarPicker('savePhoto')">
        <div class="pf-photo">
            <x-avatar :user="$user" size="96" />
            <button type="button" class="cam" @click="pick()" aria-label="{{ $user->avatar_path ? 'Changer la photo' : 'Ajouter une photo' }}"><x-icon name="plus" size="17" stroke="2.6" /></button>
            <div class="busy" x-show="busy" x-cloak>Envoi…</div>
            <input type="file" accept="image/*" x-ref="file" @change="chosen($event)" hidden>
        </div>
        <div style="min-width: 0;">
            <div class="pf-name">{{ $user->first_name }} {{ $user->last_name }}</div>
            <div class="pf-tags">
                <span>{{ $roleLabel }}</span>
                @if ($restaurant)<span>{{ $restaurant->name }}</span>@endif
            </div>
            <div class="pf-actions">
                <button type="button" class="light" @click="pick()" :disabled="busy">{{ $user->avatar_path ? 'Changer la photo' : 'Ajouter une photo' }}</button>
                @if ($user->avatar_path)
                    <button type="button" wire:click="removePhoto" wire:confirm="Retirer votre photo de profil ? Vos initiales s'afficheront à la place." data-confirm-ok="Retirer la photo">Retirer la photo</button>
                @endif
            </div>
            <div class="pf-err" x-show="error" x-text="error" x-cloak></div>
        </div>
    </div>

    {{-- ================= Informations ================= --}}
    <div class="sc-card">
        <h3><x-icon name="users" size="16" /> Mes informations</h3>
        @if (session('profile-success'))
            <div class="ok"><x-icon name="check-circle" size="18" /> {{ session('profile-success') }}</div>
        @endif
        <form wire:submit="saveInfo">
            <div class="grid2">
                <div class="field">
                    <label for="pf-first">Prénom</label>
                    <input id="pf-first" type="text" wire:model="firstName" autocomplete="given-name">
                    @error('firstName') <div class="err">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="pf-last">Nom</label>
                    <input id="pf-last" type="text" wire:model="lastName" autocomplete="family-name">
                    @error('lastName') <div class="err">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="field">
                <label for="pf-phone">Numéro de téléphone</label>
                <input id="pf-phone" type="tel" inputmode="tel" wire:model="phone" autocomplete="tel" placeholder="07 XX XX XX XX">
                <div class="help">C'est le numéro qui sert à vous connecter.</div>
                @error('phone') <div class="err">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>

    {{-- ================= Mot de passe ================= --}}
    <div class="sc-card">
        <h3><x-icon name="lock" size="16" /> Mot de passe</h3>
        @if (session('password-success'))
            <div class="ok"><x-icon name="check-circle" size="18" /> {{ session('password-success') }}</div>
        @endif
        <form wire:submit="savePassword">
            <div class="field">
                <label for="pf-current">Mot de passe actuel</label>
                <input id="pf-current" type="password" wire:model="currentPassword" autocomplete="current-password">
                @error('currentPassword') <div class="err">{{ $message }}</div> @enderror
            </div>
            <div class="grid2">
                <div class="field">
                    <label for="pf-new">Nouveau mot de passe</label>
                    <input id="pf-new" type="password" wire:model="password" autocomplete="new-password">
                    <div class="help">Au moins 8 caractères.</div>
                    @error('password') <div class="err">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="pf-confirm">Retapez-le</label>
                    <input id="pf-confirm" type="password" wire:model="passwordConfirmation" autocomplete="new-password">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
        </form>
        <p class="help" style="margin: 12px 0 0;">Votre mot de passe est enregistré chiffré. Personne ne peut le lire, pas même le patron.</p>
    </div>

    <p class="small muted" style="line-height: 1.5;">
        Votre photo est facultative. Seules les personnes de votre maquis la voient. Vous pouvez la retirer à tout moment.
    </p>
</div>
