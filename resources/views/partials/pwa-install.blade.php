{{--
    Bouton « Installer l'application ».
    - Android / Chrome : installation en un clic.
    - iPhone (Safari) : explication (Partager → Sur l'écran d'accueil).
    - Déjà installée (ouverte depuis l'icône) : rien ne s'affiche.
    $variant : 'light' (fond clair) ou 'dark' (menu vert).
--}}
@php $dark = ($variant ?? 'light') === 'dark'; @endphp
<div class="me-install" hidden wire:ignore style="margin-top: 14px;">
    <button type="button" class="me-install-btn"
            style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 14px; border-radius: 12px; cursor: pointer; font: 700 14px/1.2 inherit; font-family: inherit;
                   {{ $dark ? 'background: rgba(255,255,255,0.10); color: white; border: 1px solid rgba(255,255,255,0.18);' : 'background: var(--color-mint, #EEF6F2); color: var(--color-primary, #0B4D3C); border: 1px solid #CFE6DA;' }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M12 7v7M9 11l3 3 3-3"/></svg>
        Installer l'application
    </button>
    <div class="me-install-ios" hidden
         style="margin-top: 10px; font-size: 13px; line-height: 1.55; border-radius: 12px; padding: 12px 14px;
                {{ $dark ? 'background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.85);' : 'background: var(--color-mint, #EEF6F2); color: #10231D;' }}">
        Sur iPhone : touchez <strong>Partager</strong> <span aria-hidden="true">⬆️</span> en bas de Safari, puis <strong>« Sur l'écran d'accueil »</strong>.
    </div>
</div>

<script>
    (function () {
        var box = document.currentScript.previousElementSibling;
        if (!box || box.dataset.ready) return;
        box.dataset.ready = '1';

        var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (standalone) return; // déjà installée

        var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
        var btn = box.querySelector('.me-install-btn');
        var help = box.querySelector('.me-install-ios');

        function showForAndroid() { box.hidden = false; }

        if (window.__installPrompt) showForAndroid();
        document.addEventListener('me:installable', showForAndroid);

        if (isIos) {
            box.hidden = false;
        }

        btn.addEventListener('click', function () {
            if (window.__installPrompt) {
                window.__installPrompt.prompt();
                window.__installPrompt.userChoice.finally(function () {
                    window.__installPrompt = null;
                    box.hidden = true;
                });
            } else if (isIos) {
                help.hidden = !help.hidden;
            }
        });

        window.addEventListener('appinstalled', function () { box.hidden = true; });
    })();
</script>
