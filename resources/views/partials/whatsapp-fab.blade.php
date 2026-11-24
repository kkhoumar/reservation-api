{{--
    Bouton WhatsApp du service client, en bas à droite.
    Paramètre : $message (texte déjà écrit dans la conversation).
    Caché si aucun numéro n'est réglé (Super Admin → Réglages).
--}}
@php $waUrl = \App\Support\SupportContact::whatsappUrl($message ?? 'Bonjour, je vous contacte depuis Maquis Express.'); @endphp
@if ($waUrl)
    <style>
        .me-wa { position: fixed; right: 16px; bottom: calc(18px + env(safe-area-inset-bottom)); z-index: 40;
            display: flex; align-items: center; gap: 10px; pointer-events: none; }
        .me-wa a.fab { pointer-events: auto; position: relative; width: 58px; height: 58px; border-radius: 50%; flex-shrink: 0;
            display: grid; place-items: center; background: #25D366; color: #fff; text-decoration: none;
            box-shadow: 0 10px 24px rgba(18,140,74,.38), 0 2px 6px rgba(0,0,0,.12);
            transition: transform .18s cubic-bezier(.2,.9,.3,1.4), box-shadow .18s ease; -webkit-tap-highlight-color: transparent; }
        .me-wa a.fab:hover { transform: scale(1.06); box-shadow: 0 14px 30px rgba(18,140,74,.45), 0 2px 6px rgba(0,0,0,.12); }
        .me-wa a.fab:active { transform: scale(.94); }
        .me-wa a.fab::after { content: ''; position: absolute; inset: 0; border-radius: 50%; border: 2px solid #25D366; opacity: 0;
            animation: me-wa-ring 2.4s ease-out 1.2s 2; }
        .me-wa .hint { pointer-events: auto; position: relative; background: #fff; color: #10231D; border-radius: 14px; padding: 10px 30px 10px 14px;
            font-size: 14px; line-height: 1.35; box-shadow: 0 10px 28px rgba(6,46,36,.18); max-width: 220px;
            opacity: 0; transform: translateX(8px) scale(.96); transition: opacity .25s ease, transform .25s ease; }
        .me-wa .hint.on { opacity: 1; transform: none; }
        .me-wa .hint strong { display: block; font-size: 14px; }
        .me-wa .hint span { color: #5B6B65; font-size: 13px; }
        .me-wa .hint::after { content: ''; position: absolute; right: -6px; top: 50%; width: 12px; height: 12px; background: #fff;
            transform: translateY(-50%) rotate(45deg); border-radius: 2px; }
        .me-wa .hint button { position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border: none; background: none;
            color: #8A9A94; font-size: 18px; line-height: 1; cursor: pointer; border-radius: 6px; }
        .me-wa .hint[hidden] { display: none; }
        .me-wa { transition: bottom .35s ease, opacity .2s ease; }
        /* Barre « Installer l'application » (page d'accueil) : le bouton monte au-dessus */
        body:has(.app-sticky.show) .me-wa { bottom: calc(94px + env(safe-area-inset-bottom)); }
        /* Le bandeau « pas de connexion » passe devant : le bouton s'efface */
        body:has(#me-net.show) .me-wa { opacity: 0; visibility: hidden; }
        @media (min-width: 861px) { .me-wa { right: 24px; bottom: 24px; } }
        @media print { .me-wa { display: none; } }
        @media (prefers-reduced-motion: reduce) { .me-wa a.fab::after { animation: none; } .me-wa .hint { transition: none; } }
        @keyframes me-wa-ring { 0% { opacity: .7; transform: scale(1); } 100% { opacity: 0; transform: scale(1.5); } }
    </style>
    <div class="me-wa">
        <div class="hint" id="me-wa-hint" role="status" hidden>
            <button type="button" aria-label="Fermer" onclick="meWaHide()">×</button>
            <strong>Besoin d'aide ?</strong>
            <span>Écrivez-nous sur WhatsApp.</span>
        </div>
        <a class="fab" href="{{ $waUrl }}" target="_blank" rel="noopener" aria-label="Écrire à Maquis Express sur WhatsApp" title="Écrire sur WhatsApp" onclick="meWaHide()">
            @include('partials.whatsapp-logo', ['size' => 30])
        </a>
    </div>
    <script>
        (function () {
            var KEY = 'me-wa-hint-seen';
            var hint = document.getElementById('me-wa-hint');
            window.meWaHide = function () {
                if (!hint) return;
                hint.classList.remove('on');
                setTimeout(function () { hint.hidden = true; }, 260);
                try { localStorage.setItem(KEY, '1'); } catch (e) {}
            };
            var seen = false;
            try { seen = localStorage.getItem(KEY) === '1'; } catch (e) {}
            if (seen || !hint) return;
            // Une seule fois : la bulle apparaît, puis disparaît toute seule
            setTimeout(function () {
                hint.hidden = false;
                requestAnimationFrame(function () { hint.classList.add('on'); });
                try { localStorage.setItem(KEY, '1'); } catch (e) {}
                setTimeout(window.meWaHide, 7000);
            }, 2500);
        })();
    </script>
@endif
