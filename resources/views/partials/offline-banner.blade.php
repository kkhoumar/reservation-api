{{--
    Bandeau de connexion (marche 1 du mode hors ligne) :
    - internet coupe : bandeau orange, les saisies restent à l'écran ;
    - un envoi n'est pas parti : on le dit clairement (avant, rien ne se passait) ;
    - le serveur se réveille (offre gratuite) ou la session a expiré : message en français ;
    - internet revient : bandeau vert quelques secondes, on peut continuer.
    Rien n'est renvoyé tout seul : un encaissement ne doit jamais partir deux fois.
--}}
<style>
    #me-net {
        position: fixed; left: 50%; bottom: calc(14px + env(safe-area-inset-bottom, 0px)); z-index: 9998;
        transform: translate(-50%, 140%); opacity: 0;
        width: min(460px, calc(100% - 24px));
        display: flex; gap: 12px; align-items: flex-start;
        padding: 12px 14px; border-radius: 14px; color: #fff;
        font: 500 14px/1.4 'Inter', -apple-system, sans-serif;
        box-shadow: 0 14px 34px rgba(6, 46, 36, .28);
        transition: transform .3s ease, opacity .3s ease, background .3s ease;
        pointer-events: none;
    }
    #me-net.show { transform: translate(-50%, 0); opacity: 1; pointer-events: auto; }
    #me-net[data-state="offline"] { background: #B5501D; }
    #me-net[data-state="waking"] { background: #146652; }
    #me-net[data-state="expired"] { background: #10231D; }
    #me-net[data-state="online"], #me-net[data-state="sent"] { background: #1E8E5A; }
    #me-net[data-state="wait"] { background: #10231D; }
    #me-net .t { font-weight: 800; font-size: 15px; }
    #me-net .s { opacity: .92; margin-top: 2px; }
    #me-net .s:empty { display: none; }
    #me-net .ic { flex-shrink: 0; width: 22px; height: 22px; margin-top: 1px; }
    #me-net button {
        margin-left: auto; flex-shrink: 0; align-self: center;
        background: rgba(255,255,255,.18); color: #fff; border: 1px solid rgba(255,255,255,.35);
        border-radius: 10px; padding: 8px 12px; font: 700 13px/1 inherit; font-family: inherit; cursor: pointer; min-height: 36px;
    }
    #me-net button[hidden] { display: none; }
    #me-net.nudge { animation: me-net-nudge .4s ease; }
    @keyframes me-net-nudge { 0%,100% { transform: translate(-50%, 0); } 25% { transform: translate(calc(-50% - 6px), 0); } 75% { transform: translate(calc(-50% + 6px), 0); } }
    @media (prefers-reduced-motion: reduce) { #me-net, #me-net.nudge { transition: none; animation: none; } }
</style>
<div id="me-net" role="status" aria-live="polite" data-state="online">
    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M5 12.6a10 10 0 0 1 14 0"/><path d="M8.5 16a5 5 0 0 1 7 0"/><path d="M12 19.5h.01"/><path d="M1.5 9a15 15 0 0 1 21 0"/>
    </svg>
    <div>
        <div class="t"></div>
        <div class="s"></div>
    </div>
    <button type="button" hidden></button>
</div>
@php
    $meUser = auth()->user();
    $meCanSell = \App\Actions\RecordOfflineSale::canUse($meUser);
    $meContext = $meUser && $meUser->currentRestaurantId()
        ? ['r' => $meUser->currentRestaurantId(), 'u' => $meUser->id, 'name' => $meUser->first_name, 'canSell' => $meCanSell]
        : null;
@endphp
<script src="/js/offline-sales.js"></script>
<script>
    // Carnet de secours : compte connecté, copie des produits et envoi des ventes gardées
    (function () {
        var ME = window.MEOffline;
        if (!ME) return;
        @auth
            ME.setCurrent(@json($meContext));
            ME.token = @json(csrf_token());
            if (ME.canSell() && navigator.onLine !== false) {
                var cat = ME.catalog();
                if (!cat || !cat.fetched_at || Date.now() - cat.fetched_at > 2 * 60 * 1000) ME.refreshCatalog();
                ME.warmPage();
                ME.sync();
            }

            // Déconnexion : impossible tant que des ventes de secours attendent d'être envoyées
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!form.action || form.action.indexOf('/logout') === -1) return;
                var n = ME.pending();
                if (n > 0) {
                    e.preventDefault();
                    alert('Il reste ' + n + (n > 1 ? ' ventes de secours' : ' vente de secours') + ' dans ce téléphone. Attendez qu\'internet revienne pour les envoyer avant de vous déconnecter.');
                    return;
                }
                ME.setCurrent(null);
            }, true);
        @endauth
    })();
</script>
<script>
    (function () {
        var box = document.getElementById('me-net');
        if (!box || box.dataset.ready) return;
        box.dataset.ready = '1';

        var title = box.querySelector('.t');
        var sub = box.querySelector('.s');
        var btn = box.querySelector('button');
        var state = 'online';
        var pollTimer = null;
        var hideTimer = null;

        var TEXTS = {
            offline: ['Pas d\'internet', 'Vos saisies restent à l\'écran. Rien n\'est perdu.'],
            waking: ['Le serveur se réveille', 'Patientez quelques secondes, puis réessayez.'],
            expired: ['Session expirée', 'Rechargez la page pour continuer.'],
            online: ['Connexion revenue', 'Vous pouvez continuer.'],
            sent: ['Ventes de secours envoyées', ''],
            wait: ['Ventes de secours en attente', '']
        };

        var ME = window.MEOffline;
        var canSell = function () { return !!(ME && ME.canSell() && ME.catalog()); };
        @auth
            @unless ($meCanSell)
                TEXTS.offline[1] = 'Passez les commandes au comptoir, à voix haute ou sur papier.';
            @endunless
        @endauth

        function show(next, extra) {
            clearTimeout(hideTimer);
            state = next;
            box.dataset.state = next;
            title.textContent = TEXTS[next][0];
            sub.textContent = extra || TEXTS[next][1];
            var rescue = next === 'offline' && canSell();
            btn.hidden = next !== 'expired' && !rescue;
            btn.textContent = rescue ? 'Vente de secours' : 'Recharger';
            btn.dataset.action = rescue ? 'rescue' : 'reload';
            box.classList.add('show');

            if (next === 'online' || next === 'sent') {
                hideTimer = setTimeout(function () { box.classList.remove('show'); }, 3500);
            }
        }

        function nudge() {
            box.classList.remove('nudge');
            void box.offsetWidth;
            box.classList.add('nudge');
        }

        btn.addEventListener('click', function () {
            if (btn.dataset.action === 'rescue') location.href = '/secours';
            else location.reload();
        });

        function fcfa(n) { return String(Math.round(n || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' F'; }

        // Résultat de l'envoi du carnet de secours
        document.addEventListener('me-offline:synced', function (e) {
            var d = e.detail || {};
            if (d.saved) {
                show('sent', d.saved + (d.saved > 1 ? ' ventes enregistrées' : ' vente enregistrée') + ' dans la caisse : ' + fcfa(d.total) + '.');
            } else if (d.wait) {
                show('wait', d.wait);
            } else if (d.rejected) {
                show('wait', 'Une vente de secours a été refusée. Ouvrez la page Vente de secours pour la vérifier.');
            }
        });

        // Vraie vérification : le Wi-Fi peut être allumé sans internet
        function check() {
            var ctrl = window.AbortController ? new AbortController() : null;
            var t = ctrl ? setTimeout(function () { ctrl.abort(); }, 6000) : null;
            return fetch('/up', { cache: 'no-store', credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined })
                .then(function (r) { return r.ok; })
                .catch(function () { return false; })
                .finally(function () { if (t) clearTimeout(t); });
        }

        function startPolling() {
            if (pollTimer) return;
            pollTimer = setInterval(function () {
                check().then(function (ok) {
                    if (ok) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                        if (state === 'offline' || state === 'waking') show('online');
                    }
                });
            }, 4000);
        }

        function goOffline(extra) {
            if (state !== 'offline') show('offline', extra);
            else if (extra) { sub.textContent = extra; nudge(); }
            startPolling();
        }

        window.addEventListener('offline', function () { goOffline(); });
        window.addEventListener('online', function () {
            check().then(function (ok) { if (ok && state !== 'online' && state !== 'expired') show('online'); });
        });

        if (navigator.onLine === false) goOffline();

        var NOT_SENT = 'Le dernier envoi n\'est pas parti. Vos saisies restent à l\'écran : réessayez quand ce bandeau devient vert.';

        // Un vrai toucher de l'utilisateur, ou un rafraîchissement automatique de l'écran ?
        var lastTouch = 0;
        ['pointerdown', 'keydown', 'input', 'change'].forEach(function (ev) {
            document.addEventListener(ev, function () { lastTouch = Date.now(); }, true);
        });
        function byUser() { return Date.now() - lastTouch < 3000; }

        document.addEventListener('livewire:init', function () {
            if (!window.Livewire || !Livewire.hook) return;

            Livewire.hook('request', function (args) {
                if (!args || !args.fail) return;

                args.fail(function (f) {
                    // Pas de réponse du tout : internet coupé
                    if (f.content === null || f.content === undefined) {
                        goOffline(byUser() ? NOT_SENT : null);
                        return;
                    }

                    // Le serveur gratuit dort ou redémarre
                    if (f.status >= 502 && f.status <= 504) {
                        if (f.preventDefault) f.preventDefault();
                        show('waking', byUser() ? 'Le dernier envoi n\'est pas parti. Réessayez dans quelques secondes.' : null);
                        startPolling();
                        return;
                    }

                    // Page restée ouverte trop longtemps : message en français
                    if (f.status === 419) {
                        if (f.preventDefault) f.preventDefault();
                        show('expired');
                    }
                });

                if (args.succeed) {
                    args.succeed(function () {
                        // Réponse partie juste avant la coupure : on ne crie pas victoire trop tôt
                        if (navigator.onLine === false) return;
                        if (state === 'offline' || state === 'waking') show('online');
                    });
                }
            });
        });
    })();
</script>
