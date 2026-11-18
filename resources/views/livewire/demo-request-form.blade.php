<div class="demo">
    <style>
        .demo { display: grid; grid-template-columns: 0.95fr 1.05fr; gap: 48px; align-items: start; padding: 48px 0 24px; }
        .demo .eyebrow { display: inline-block; background: rgba(193,84,30,0.1); color: var(--color-accent); font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 999px; }
        .demo h1 { font-size: 36px; font-weight: 900; line-height: 1.1; margin: 16px 0 14px; letter-spacing: -0.03em; }
        .demo .intro { font-size: 16px; color: var(--color-text-secondary); line-height: 1.6; margin: 0 0 28px; }
        .demo .steps { display: flex; flex-direction: column; gap: 18px; margin-bottom: 28px; }
        .demo .step { display: flex; gap: 14px; align-items: flex-start; }
        .demo .step .n { flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; background: var(--color-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; }
        .demo .step strong { display: block; font-size: 15px; }
        .demo .step span { font-size: 14px; color: var(--color-text-secondary); line-height: 1.5; }
        .demo .reassure { background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 16px 18px; font-size: 14px; line-height: 1.9; }
        .demo .reassure div::before { content: '✓  '; color: var(--color-primary-300); font-weight: 800; }

        .demo .form-card { background: white; border: 1px solid var(--color-border); border-radius: 20px; padding: 28px; box-shadow: 0 24px 48px rgba(11,77,60,0.10); }
        .demo .form-card h2 { font-size: 22px; margin: 0 0 4px; }
        .demo .field { margin-bottom: 14px; }
        .demo label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .demo label .opt { font-weight: 500; color: var(--color-text-secondary); }
        .demo input, .demo select, .demo textarea {
            width: 100%; font-family: inherit; font-size: 15px; padding: 12px 14px;
            border: 1px solid var(--color-border); border-radius: 10px; background: #fff; color: var(--color-text);
        }
        .demo textarea { resize: vertical; min-height: 70px; }
        .demo input:focus, .demo select:focus, .demo textarea:focus { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 3px rgba(193,84,30,0.12); }
        .demo .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .demo .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .demo .chip { border: 1px solid var(--color-border); background: white; border-radius: 999px; padding: 8px 14px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--color-text); }
        .demo .chip.on { background: var(--color-primary); border-color: var(--color-primary); color: white; }
        .demo .error { color: var(--color-danger); font-size: 13px; margin-top: 5px; }
        .demo .submit { width: 100%; background: var(--color-accent); color: white; border: none; border-radius: 12px; padding: 15px; font-size: 16px; font-weight: 800; font-family: inherit; cursor: pointer; margin-top: 6px; }
        .demo .submit:disabled { opacity: .6; }
        .demo .small { font-size: 12px; color: var(--color-text-secondary); line-height: 1.5; margin: 12px 0 0; }

        .demo .done { text-align: center; padding: 12px 4px; }
        .demo .done .ok { width: 64px; height: 64px; border-radius: 50%; background: rgba(94,191,160,0.18); color: var(--color-primary); font-size: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }

        @media (max-width: 900px) {
            .demo { grid-template-columns: 1fr; gap: 28px; padding-top: 28px; }
            .demo .form-card { order: -1; }
            .demo h1 { font-size: 28px; }
        }
        @media (max-width: 520px) {
            .demo .row-2 { grid-template-columns: 1fr; }
            .demo .form-card { padding: 20px; }
        }
    </style>

    {{-- ================= Colonne explications ================= --}}
    <div>
        <span class="eyebrow">Démo gratuite et sans engagement</span>
        <h1>Voyons ensemble comment Maquis Express peut aider votre maquis</h1>
        <p class="intro">Laissez-nous votre numéro : on vous rappelle, on vous montre l'application et on l'installe avec vous.</p>

        <div class="steps">
            <div class="step"><div class="n">1</div><div><strong>On vous appelle</strong><span>Pour comprendre votre maquis : vos tables, votre équipe, votre façon de travailler.</span></div></div>
            <div class="step"><div class="n">2</div><div><strong>On installe votre maquis</strong><span>Votre compte, votre menu, vos tables et les accès de votre équipe.</span></div></div>
            <div class="step"><div class="n">3</div><div><strong>Vous essayez {{ $trialDays }} jours gratuitement</strong><span>En vrai, pendant le service. Vous décidez ensuite.</span></div></div>
        </div>

        <div class="reassure">
            <div>Aucun paiement pour la démo et l'essai</div>
            <div>Marche sur les téléphones que vous avez déjà</div>
            <div>Espèces, Wave, Orange Money, MTN et Moov</div>
            <div>Le rapport du soir sur votre WhatsApp</div>
        </div>
    </div>

    {{-- ================= Formulaire ================= --}}
    <div class="form-card">
        @if ($sent)
            <div class="done">
                <div class="ok">✓</div>
                <h2>Merci, c'est noté !</h2>
                <p style="font-size: 15px; color: var(--color-text-secondary); line-height: 1.6; margin: 10px 0 22px;">
                    Nous vous appelons très vite au numéro indiqué pour vous présenter Maquis Express et préparer votre maquis.
                    Gardez votre téléphone à portée de main.
                </p>
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-secondary-outline" style="display: block; text-align: center; margin-bottom: 10px;">Nous écrire sur WhatsApp</a>
                @endif
                <a href="{{ route('landing') }}" class="btn-primary" style="display: block; text-align: center;">Retour à l'accueil</a>
            </div>
        @else
            <h2>Demander une démo</h2>
            <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0 0 20px;">Ça prend 1 minute.</p>

            <form wire:submit="submit">
                <div class="row-2">
                    <div class="field">
                        <label for="contactName">Votre nom</label>
                        <input id="contactName" type="text" wire:model="contactName" placeholder="Koffi Yao" autocomplete="name">
                        @error('contactName') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="phone">Téléphone / WhatsApp</label>
                        <input id="phone" type="tel" wire:model="phone" inputmode="tel" placeholder="07 XX XX XX XX" autocomplete="tel">
                        @error('phone') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row-2">
                    <div class="field">
                        <label for="restaurantName">Nom de l'établissement</label>
                        <input id="restaurantName" type="text" wire:model="restaurantName" placeholder="Maquis Chez Koffi">
                        @error('restaurantName') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="commune">Commune <span class="opt">(facultatif)</span></label>
                        <input id="commune" type="text" wire:model="commune" placeholder="Yopougon, Cocody, Bouaké…">
                        @error('commune') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field">
                    <label>Type d'établissement <span class="opt">(facultatif)</span></label>
                    <div class="chips">
                        @foreach ($types as $type)
                            <button type="button" wire:click="$set('establishmentType', @js($establishmentType === $type ? '' : $type))" class="chip {{ $establishmentType === $type ? 'on' : '' }}">{{ $type }}</button>
                        @endforeach
                    </div>
                    @error('establishmentType') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="teamSize">Votre équipe <span class="opt">(facultatif)</span></label>
                    <select id="teamSize" wire:model="teamSize">
                        <option value="">— Choisir —</option>
                        @foreach ($teamSizes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('teamSize') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="message">Un mot pour nous <span class="opt">(facultatif)</span></label>
                    <textarea id="message" wire:model="message" maxlength="400" placeholder="Ex : 12 tables, on vend surtout des boissons, je veux surveiller le stock"></textarea>
                    @error('message') <div class="error">{{ $message }}</div> @enderror
                </div>

                {{-- Piège à robots : invisible pour les humains --}}
                <div style="position: absolute; left: -10000px;" aria-hidden="true">
                    <label>Site web</label>
                    <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit" class="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Être rappelé</span>
                    <span wire:loading wire:target="submit">Envoi…</span>
                </button>
                <p class="small">
                    Vos informations servent uniquement à vous rappeler.
                    Voir notre <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">politique de confidentialité</a>.
                </p>
            </form>

            <p style="text-align: center; font-size: 14px; color: var(--color-text-secondary); margin: 18px 0 0;">
                Déjà client ? <a href="{{ route('login') }}" style="color: var(--color-accent); font-weight: 700;">Se connecter</a>
                @if ($whatsappUrl)
                    · <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" style="color: var(--color-accent); font-weight: 700;">Écrire sur WhatsApp</a>
                @endif
            </p>
        @endif
    </div>
</div>
