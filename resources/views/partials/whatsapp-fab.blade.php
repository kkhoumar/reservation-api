{{--
    Service client WhatsApp : bouton vert en bas à droite + bulle de discussion.

    Paramètres :
      $message  texte déjà écrit dans WhatsApp (bouton vert)
      $mode     'visitor' : la bulle s'ouvre après 10 s, puis une fois aux tarifs
                'login'   : la bulle s'ouvre seulement après 2 mots de passe ratés
                'quiet'   : jamais de bulle (écrans de travail du patron et du gérant)
      $help     texte déjà écrit pour le bouton de la bulle (sinon $message)

    Règles : la bulle ressort à chaque nouvelle visite ; fermée, elle ne revient
    pas pendant la même visite (même en changeant de page) ; « répond en moins
    d'1 heure » seulement de 8 h à 22 h (heure d'Abidjan), sinon « dès 8 h ».
    Caché si aucun numéro n'est réglé (Super Admin → Réglages).
--}}
@php
    $waMode = $mode ?? 'quiet';
    $waUrl = \App\Support\SupportContact::whatsappUrl($message ?? 'Bonjour, je vous contacte depuis Maquis Express.');
    $waHelpUrl = \App\Support\SupportContact::whatsappUrl($help ?? $message ?? 'Bonjour, je vous contacte depuis Maquis Express.');
@endphp
@if ($waUrl)
    <style>
        .me-wa { position: fixed; right: 16px; bottom: calc(18px + env(safe-area-inset-bottom)); z-index: 40;
            display: flex; flex-direction: column; align-items: flex-end; gap: 12px; pointer-events: none;
            transition: bottom .35s ease, opacity .2s ease; font-family: inherit; }
        .me-wa a.fab { pointer-events: auto; position: relative; width: 58px; height: 58px; border-radius: 50%; flex-shrink: 0;
            display: grid; place-items: center; background: #25D366; color: #fff; text-decoration: none;
            box-shadow: 0 10px 24px rgba(18,140,74,.38), 0 2px 6px rgba(0,0,0,.12);
            transition: transform .18s cubic-bezier(.2,.9,.3,1.4), box-shadow .18s ease; -webkit-tap-highlight-color: transparent; }
        .me-wa a.fab:hover { transform: scale(1.06); box-shadow: 0 14px 30px rgba(18,140,74,.45), 0 2px 6px rgba(0,0,0,.12); }
        .me-wa a.fab:active { transform: scale(.94); }
        .me-wa a.fab .dot { position: absolute; top: 2px; right: 2px; width: 14px; height: 14px; border-radius: 50%;
            background: #D2622A; border: 2px solid #fff; display: none; }
        .me-wa.unread a.fab .dot { display: block; }

        /* Bulle de discussion */
        .me-wa .card { pointer-events: auto; width: min(320px, calc(100vw - 32px)); background: #fff; border-radius: 18px; overflow: hidden;
            box-shadow: 0 22px 50px rgba(6,46,36,.26), 0 2px 8px rgba(0,0,0,.08); transform-origin: bottom right;
            opacity: 0; transform: translateY(10px) scale(.94); transition: opacity .25s ease, transform .3s cubic-bezier(.2,.9,.3,1.2); }
        .me-wa .card.on { opacity: 1; transform: none; }
        .me-wa .card[hidden] { display: none; }
        .me-wa .head { display: flex; align-items: center; gap: 10px; padding: 14px 12px 12px 14px; background: #0B4D3C; color: #fff; }
        .me-wa .av { position: relative; width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; display: grid; place-items: center;
            background: linear-gradient(145deg, #E07A43, #C4541D); font-weight: 800; font-size: 15px; letter-spacing: -.02em; }
        .me-wa .av::after { content: ''; position: absolute; right: -3px; bottom: -3px; width: 12px; height: 12px; border-radius: 50%;
            background: #9AA8A3; border: 2px solid #0B4D3C; }
        .me-wa .card.open-now .av::after { background: #3DDC84; }
        .me-wa .who { flex: 1; min-width: 0; line-height: 1.25; }
        .me-wa .who strong { display: block; font-size: 15px; }
        .me-wa .who small { font-size: 12px; opacity: .8; }
        .me-wa .x { width: 30px; height: 30px; border: none; border-radius: 9px; background: rgba(255,255,255,.12); color: #fff;
            font-size: 18px; line-height: 1; cursor: pointer; flex-shrink: 0; }
        .me-wa .body { padding: 16px 14px 14px; background: #EFEAE2; }
        .me-wa .msg { position: relative; background: #fff; color: #10231D; border-radius: 4px 14px 14px 14px; padding: 10px 12px 18px;
            font-size: 14px; line-height: 1.45; box-shadow: 0 1px 1px rgba(0,0,0,.08); max-width: 92%; }
        .me-wa .msg time { position: absolute; right: 10px; bottom: 4px; font-size: 10px; color: #8A9A94; }
        .me-wa .cta { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 12px 0 0; padding: 12px;
            border-radius: 12px; background: #25D366; color: #fff; font-weight: 800; font-size: 15px; text-decoration: none;
            box-shadow: 0 6px 16px rgba(18,140,74,.3); }
        .me-wa .cta:active { transform: scale(.98); }

        /* Barre « Installer l'application » (page d'accueil) : le bouton monte au-dessus */
        body:has(.app-sticky.show) .me-wa { bottom: calc(94px + env(safe-area-inset-bottom)); }
        /* Le bandeau « pas de connexion » passe devant : le bouton s'efface */
        body:has(#me-net.show) .me-wa { opacity: 0; visibility: hidden; }
        @media (min-width: 861px) { .me-wa { right: 24px; bottom: 24px; } }
        @media print { .me-wa { display: none; } }
        @media (prefers-reduced-motion: reduce) { .me-wa .card { transition: none; } }
    </style>

    <div class="me-wa" id="me-wa" data-mode="{{ $waMode }}">
        @if ($waMode !== 'quiet')
            <div class="card" id="me-wa-card" role="dialog" aria-label="Service client Maquis Express" hidden>
                <div class="head">
                    <span class="av" aria-hidden="true">ME</span>
                    <span class="who">
                        <strong>Équipe Maquis Express</strong>
                        <small id="me-wa-status">Répond en général en moins d'1 heure</small>
                    </span>
                    <button type="button" class="x" aria-label="Fermer" onclick="meWa.close()">×</button>
                </div>
                <div class="body">
                    <div class="msg"><span id="me-wa-text"></span><time id="me-wa-time"></time></div>
                    <a class="cta" href="{{ $waHelpUrl }}" target="_blank" rel="noopener" onclick="meWa.close()">
                        @include('partials.whatsapp-logo', ['size' => 20])
                        Écrire sur WhatsApp
                    </a>
                </div>
            </div>
        @endif
        <a class="fab" href="{{ $waUrl }}" target="_blank" rel="noopener" aria-label="Écrire à Maquis Express sur WhatsApp" title="Écrire sur WhatsApp" onclick="window.meWa && meWa.close()">
            @include('partials.whatsapp-logo', ['size' => 30])
            <span class="dot" aria-hidden="true"></span>
        </a>
    </div>

    @if ($waMode !== 'quiet')
        <script>
            (function () {
                var root = document.getElementById('me-wa');
                var card = document.getElementById('me-wa-card');
                if (!root || !card) return;

                var mode = root.dataset.mode;
                // Une « visite » = tant que le navigateur reste ouvert (sessionStorage).
                // Nouvelle visite (le lendemain, Chrome rouvert…) : la bulle ressort.
                var KEY = 'me-wa-visit';
                var texts = {
                    hello: 'Bonjour 👋 Une question pour votre maquis ? On vous répond sur WhatsApp.',
                    prices: 'Vous hésitez entre les formules ? On vous conseille gratuitement sur WhatsApp.',
                    login: 'Problème pour vous connecter ? Écrivez-nous, on vous aide tout de suite.'
                };
                var hideTimer = null;

                // Ce qui s'est passé pendant cette visite : bulles déjà montrées, bulle fermée
                function visit() {
                    try { return JSON.parse(sessionStorage.getItem(KEY)) || {}; } catch (e) { return {}; }
                }
                function remember(field) {
                    var v = visit(); v[field] = true;
                    try { sessionStorage.setItem(KEY, JSON.stringify(v)); } catch (e) {}
                }
                function snoozed() { return !!visit().closed; }

                // Abidjan est à l'heure UTC toute l'année
                function openNow() { var h = new Date().getUTCHours(); return h >= 8 && h < 22; }

                function show(kind, force) {
                    if (!force && (visit()[kind] || snoozed())) return;
                    if (document.querySelector('#me-net.show')) return;
                    remember(kind);

                    var now = new Date();
                    document.getElementById('me-wa-text').textContent = texts[kind];
                    document.getElementById('me-wa-time').textContent = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);
                    var open = openNow();
                    card.classList.toggle('open-now', open);
                    document.getElementById('me-wa-status').textContent = open
                        ? "Répond en général en moins d'1 heure"
                        : 'Écrivez-nous, réponse dès 8 h';

                    card.hidden = false;
                    requestAnimationFrame(function () { card.classList.add('on'); });
                    root.classList.remove('unread');
                    try { navigator.vibrate && navigator.vibrate(12); } catch (e) {}

                    // Sur téléphone, la bulle se replie toute seule pour libérer l'écran
                    clearTimeout(hideTimer);
                    hideTimer = setTimeout(function () { fold(true); }, 14000);
                }

                // Replier sans « fermer » : un point orange reste sur le bouton
                function fold(unread) {
                    card.classList.remove('on');
                    setTimeout(function () { card.hidden = true; }, 260);
                    if (unread) root.classList.add('unread');
                }

                window.meWa = {
                    show: show,
                    // Fermée par la personne : plus de bulle pendant cette visite
                    close: function () {
                        clearTimeout(hideTimer);
                        fold(false);
                        root.classList.remove('unread');
                        remember('closed');
                    }
                };

                if (mode === 'visitor') {
                    setTimeout(function () { show('hello'); }, 10000);

                    var prices = document.getElementById('tarifs');
                    if (prices && 'IntersectionObserver' in window) {
                        var seen = false;
                        new IntersectionObserver(function (entries, obs) {
                            entries.forEach(function (e) {
                                if (!e.isIntersecting || seen) return;
                                seen = true;
                                obs.disconnect();
                                // Laisser le temps de lire les prix avant de proposer l'aide
                                setTimeout(function () { show('prices'); }, 4000);
                            });
                        }, { threshold: 0.35 }).observe(prices);
                    }
                }

                // Connexion : 2 mots de passe ratés → on propose l'aide (même si la bulle a été fermée avant)
                window.addEventListener('me-wa-help', function () { show('login', true); });
            })();
        </script>
    @endif
@endif
