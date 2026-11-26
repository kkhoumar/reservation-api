{{--
    Fenêtre « Vous êtes sûr ? » aux couleurs de Maquis Express.
    Remplace la fenêtre grise du téléphone (« maquis-express.onrender.com indique »).

    - wire:confirm="Question ?  Explication" : utilisé tel quel, partout.
      Options sur le bouton : data-confirm-ok="Réactiver", data-confirm-cancel="Garder",
      data-confirm-danger (bouton rouge).
    - En JavaScript : await window.meConfirm({ title, body, ok, cancel, danger })
    Sans JavaScript moderne, rien ne change : c'est l'ancienne fenêtre qui s'affiche.
--}}
<style>
    .mc-back { position: fixed; inset: 0; z-index: 200; background: rgba(12, 28, 22, .5); display: flex; align-items: center; justify-content: center; padding: 16px; opacity: 0; transition: opacity .16s ease; }
    .mc-back.on { opacity: 1; }
    .mc { background: #fff; color: #17211C; width: 100%; max-width: 420px; border-radius: 18px; padding: 22px 22px 18px; box-shadow: 0 24px 60px rgba(0, 0, 0, .25); transform: translateY(14px) scale(.98); transition: transform .18s ease; font-family: inherit; }
    .mc-back.on .mc { transform: none; }
    .mc-ic { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; background: #E6F2EC; color: var(--color-primary, #0B4D3C); margin-bottom: 14px; }
    .mc.danger .mc-ic { background: #FDECEC; color: var(--color-danger, #D93B3B); }
    .mc h2 { margin: 0; font-size: 18px; font-weight: 800; line-height: 1.3; letter-spacing: -.01em; }
    .mc p { margin: 8px 0 0; font-size: 15px; line-height: 1.5; color: #56635C; white-space: pre-line; }
    .mc-acts { display: flex; gap: 10px; margin-top: 22px; }
    .mc-acts button { flex: 1 1 0; min-height: 48px; padding: 10px 14px; border-radius: 12px; font: inherit; font-size: 15px; font-weight: 700; cursor: pointer; border: 1px solid transparent; -webkit-tap-highlight-color: transparent; }
    .mc-no { background: #fff; border-color: #D5DCD8 !important; color: #17211C; }
    .mc-no:hover { background: #F4F6F5; }
    .mc-ok { background: var(--color-primary, #0B4D3C); color: #fff; }
    .mc.danger .mc-ok { background: var(--color-danger, #D93B3B); }
    .mc-ok:hover { filter: brightness(1.08); }
    .mc-acts button:focus-visible { outline: 3px solid rgba(11, 77, 60, .35); outline-offset: 2px; }
    body.mc-lock { overflow: hidden; }
    @media (max-width: 640px) {
        .mc-back { align-items: flex-end; padding: 0; }
        .mc { max-width: none; border-radius: 22px 22px 0 0; padding: 12px 16px calc(16px + env(safe-area-inset-bottom)); transform: translateY(100%); }
        .mc::before { content: ''; display: block; width: 40px; height: 4px; border-radius: 4px; background: #D5DCD8; margin: 0 auto 16px; }
        .mc-acts { flex-direction: column-reverse; gap: 8px; margin-top: 20px; }
        .mc-acts button { flex: none; width: 100%; min-height: 52px; font-size: 16px; }
    }
    @media (prefers-reduced-motion: reduce) { .mc-back, .mc { transition: none; } }
</style>
<script>
    (function () {
        if (window.meConfirm) return;

        // Mots qui annoncent une action qu'on ne peut pas défaire facilement → bouton rouge
        var DANGER = ['Supprimer', 'Vider', 'Retirer', 'Verrouiller', 'Désactiver', 'Effacer', 'Annuler'];
        // Le bouton dit ce qu'il fait : « Réactiver » plutôt que « OK »
        var VERBS = DANGER.concat(['Réactiver', 'Enregistrer', 'Classer', 'Réintégrer', 'Valider', 'Envoyer', 'Clôturer', 'Ouvrir']);

        var ICONS = {
            ask: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="M9.3 9.2a2.8 2.8 0 0 1 5.4 1c0 1.9-2.7 2.5-2.7 4.1"/><path d="M12 17.6h.01"/></svg>',
            danger: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 2.4 17.6A2 2 0 0 0 4.1 20.6h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9.5v4.2"/><path d="M12 17.2h.01"/></svg>'
        };

        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

        /** « Question ? Explication » → titre + texte + boutons. */
        function fromText(text, el) {
            text = String(text || '').replace(/\\n/g, '\n').trim();
            var o = { title: text, body: '' };
            var q = text.indexOf('?');
            if (q > 0 && q < 220) { o.title = text.slice(0, q + 1).trim(); o.body = text.slice(q + 1).trim(); }
            var first = o.title.split(/\s+/)[0];
            var d = (el && el.dataset) || {};
            o.ok = d.confirmOk || (VERBS.indexOf(first) >= 0 ? first : 'Oui, continuer');
            o.cancel = d.confirmCancel || (first === 'Annuler' ? 'Non, garder' : 'Annuler');
            o.danger = d.confirmDanger !== undefined ? d.confirmDanger !== 'false' : DANGER.indexOf(first) >= 0;
            return o;
        }

        var opened = null;

        window.meConfirm = function (o) {
            if (typeof o === 'string') o = fromText(o);
            if (opened) opened(false);

            return new Promise(function (resolve) {
                var back = document.createElement('div');
                back.className = 'mc-back';
                back.innerHTML = '<div class="mc' + (o.danger ? ' danger' : '') + '" role="alertdialog" aria-modal="true" aria-labelledby="mc-t" aria-describedby="mc-b">'
                    + '<div class="mc-ic" aria-hidden="true">' + (o.danger ? ICONS.danger : ICONS.ask) + '</div>'
                    + '<h2 id="mc-t">' + esc(o.title) + '</h2>'
                    + (o.body ? '<p id="mc-b">' + esc(o.body) + '</p>' : '')
                    + '<div class="mc-acts"><button type="button" class="mc-no">' + esc(o.cancel || 'Annuler') + '</button>'
                    + '<button type="button" class="mc-ok">' + esc(o.ok || 'Oui, continuer') + '</button></div></div>';

                var before = document.activeElement;
                var done = function (yes) {
                    if (!back.parentNode) return;
                    opened = null;
                    document.removeEventListener('keydown', key, true);
                    back.classList.remove('on');
                    document.body.classList.remove('mc-lock');
                    setTimeout(function () { back.remove(); }, 170);
                    if (before && before.focus) { try { before.focus({ preventScroll: true }); } catch (e) {} }
                    resolve(yes);
                };
                var key = function (e) {
                    if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); done(false); }
                    if (e.key === 'Tab') { // on reste dans la fenêtre
                        var b = back.querySelectorAll('button'), f = b[0], l = b[b.length - 1];
                        if (e.shiftKey && document.activeElement === f) { e.preventDefault(); l.focus(); }
                        else if (!e.shiftKey && document.activeElement === l) { e.preventDefault(); f.focus(); }
                    }
                };
                opened = done;
                back.addEventListener('click', function (e) { if (e.target === back) done(false); });
                back.querySelector('.mc-no').addEventListener('click', function () { done(false); });
                back.querySelector('.mc-ok').addEventListener('click', function () { done(true); });
                document.addEventListener('keydown', key, true);

                document.body.appendChild(back);
                document.body.classList.add('mc-lock');
                requestAnimationFrame(function () { back.classList.add('on'); });
                // Action dangereuse : on se place sur « Annuler » (un Entrée de trop ne supprime rien)
                back.querySelector(o.danger ? '.mc-no' : '.mc-ok').focus({ preventScroll: true });
            });
        };
        window.meConfirm.fromText = fromText;

        // wire:confirm de Livewire : notre fenêtre au lieu de celle du téléphone
        document.addEventListener('livewire:init', function () {
            Livewire.hook('directive.init', function (p) {
                var d = p.directive;
                if (d.value !== 'confirm' || d.modifiers.indexOf('prompt') >= 0) return;
                var el = p.el;
                el.__livewire_confirm = function (action, instead) {
                    instead(); // on arrête le clic ; l'action repart si on confirme
                    window.meConfirm(fromText(d.expression, el)).then(function (yes) { if (yes) action(); });
                };
            });
        });
    })();
</script>
