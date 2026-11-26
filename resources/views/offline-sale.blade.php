<!DOCTYPE html>
{{--
    Vente de secours : marche SANS internet.
    Cette page est gardée dans le téléphone par le service worker. Elle ne contient
    aucune donnée du maquis : produits, prix et ventes viennent du carnet du
    téléphone (public/js/offline-sales.js), propre à chaque compte.
--}}
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B4D3C">
    <meta name="robots" content="noindex">
    <title>Vente de secours — Maquis Express</title>
    <link rel="icon" type="image/png" sizes="64x64" href="/icons/favicon-64.png">
    <style>
        :root {
            --primary: #0B4D3C; --primary-700: #146652; --mint: #EEF6F2; --accent: #D2622A; --accent-dark: #B5501D;
            --danger: #D93B3B; --ok: #1E8E5A; --text: #10231D; --muted: #5B6B65; --border: #DDE6E1; --bg: #F4F7F5;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif; color: var(--text); background: var(--bg);
            -webkit-font-smoothing: antialiased; padding-bottom: 260px; }
        button { font-family: inherit; cursor: pointer; -webkit-tap-highlight-color: transparent; touch-action: manipulation; }
        button:active:not(:disabled) { transform: scale(.97); }
        [hidden] { display: none !important; }

        header { position: sticky; top: 0; z-index: 5; background: var(--primary); color: #fff; padding: 12px 16px calc(12px);
            padding-top: calc(12px + env(safe-area-inset-top, 0px)); display: flex; align-items: center; gap: 12px; }
        header a { color: #fff; text-decoration: none; display: inline-flex; width: 38px; height: 38px; align-items: center; justify-content: center;
            border-radius: 10px; background: rgba(255,255,255,.12); flex-shrink: 0; }
        header h1 { font-size: 17px; margin: 0; font-weight: 800; letter-spacing: -.01em; }
        header .who { font-size: 12px; opacity: .8; margin-top: 1px; }
        .net { margin-left: auto; font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 999px; white-space: nowrap; }
        .net.off { background: #F2A26F; color: #4A1F08; }
        .net.on { background: #5EBFA0; color: #062E24; }

        main { max-width: 760px; margin: 0 auto; padding: 14px 16px; }
        .note { border-radius: 12px; padding: 11px 13px; font-size: 13.5px; line-height: 1.5; margin-bottom: 12px; }
        .note.info { background: var(--mint); }
        .note.warn { background: #FFF1E6; color: #7A3410; border: 1px solid #F6D2B8; }
        .note.bad { background: #FDECEC; color: #A32626; border: 1px solid #F6C9C9; }
        .note strong { font-weight: 800; }

        .cats { display: flex; gap: 6px; overflow-x: auto; padding: 2px 0 10px; scrollbar-width: none; }
        .cats::-webkit-scrollbar { display: none; }
        .cat { flex-shrink: 0; border: 1px solid var(--border); background: #fff; color: var(--text); border-radius: 999px;
            padding: 8px 13px; font-size: 13px; font-weight: 700; min-height: 38px; }
        .cat.on { background: var(--primary); border-color: var(--primary); color: #fff; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
        .prod { position: relative; text-align: left; background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 12px;
            min-height: 78px; display: flex; flex-direction: column; justify-content: space-between; gap: 6px; color: var(--text); }
        .prod .n { font-weight: 700; font-size: 14px; line-height: 1.25; }
        .prod .p { font-weight: 800; color: var(--primary); font-size: 14px; }
        .prod .q { position: absolute; top: -6px; right: -6px; min-width: 24px; height: 24px; border-radius: 999px; background: var(--accent);
            color: #fff; font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; padding: 0 6px; }
        @media (max-width: 380px) { .grid { grid-template-columns: 1fr 1fr; } }

        h2 { font-size: 15px; margin: 22px 0 8px; display: flex; align-items: center; gap: 8px; }
        h2 .count { background: var(--border); border-radius: 999px; font-size: 12px; padding: 2px 8px; }
        .list { background: #fff; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .row { display: flex; gap: 10px; align-items: center; padding: 11px 13px; border-top: 1px solid var(--border); font-size: 13.5px; }
        .row:first-child { border-top: none; }
        .row .main { flex: 1; min-width: 0; }
        .row .main div:last-child { color: var(--muted); font-size: 12.5px; margin-top: 2px; }
        .row .amt { font-weight: 800; white-space: nowrap; }
        .row.bad { background: #FFF7F7; }
        .row.bad .main div:last-child { color: var(--danger); }
        .row button { border: 1px solid var(--border); background: #fff; border-radius: 10px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; min-height: 34px; }
        .empty { color: var(--muted); font-size: 13.5px; padding: 14px; }
        .send { width: 100%; margin-top: 10px; border: 1px solid var(--primary); background: #fff; color: var(--primary); border-radius: 12px;
            padding: 12px; font-weight: 800; font-size: 14px; min-height: 46px; }

        /* Panier en bas de l'écran */
        .cart { position: fixed; left: 0; right: 0; bottom: 0; z-index: 6; background: #fff; border-top: 1px solid var(--border);
            box-shadow: 0 -14px 34px rgba(6,46,36,.12); padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px)); }
        .cart-in { max-width: 760px; margin: 0 auto; }
        .lines { max-height: 26vh; overflow-y: auto; margin-bottom: 8px; }
        .line { display: flex; align-items: center; gap: 8px; padding: 4px 0; font-size: 14px; }
        .line .ln { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .line .lt { font-weight: 700; white-space: nowrap; min-width: 70px; text-align: right; }
        .step { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--border); background: #fff; font-size: 18px; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center; color: var(--text); }
        .line .lq { min-width: 22px; text-align: center; font-weight: 800; }
        .tot { display: flex; justify-content: space-between; align-items: baseline; font-size: 15px; margin: 2px 0 8px; }
        .tot strong { font-size: 22px; font-weight: 800; letter-spacing: -.02em; }
        .bills { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
        .bill { border: 1px solid var(--border); background: #fff; color: var(--text); border-radius: 999px; padding: 8px 12px; font-size: 13px; font-weight: 700; min-height: 38px; }
        .bill.on { background: var(--primary); border-color: var(--primary); color: #fff; }
        .cashrow { display: flex; gap: 10px; align-items: center; }
        .cashrow input { flex: 1; min-width: 0; font: 700 18px/1 inherit; font-family: inherit; padding: 11px 12px; border-radius: 12px; border: 1.5px solid var(--border); }
        .cashrow input:focus { outline: none; border-color: #5EBFA0; box-shadow: 0 0 0 4px rgba(94,191,160,.18); }
        .change { font-size: 13px; min-width: 120px; text-align: right; }
        .change strong { display: block; font-size: 17px; }
        .change.ok strong { color: var(--ok); }
        .change.miss strong { color: var(--danger); }
        .go { width: 100%; margin-top: 10px; border: none; background: var(--accent); color: #fff; border-radius: 12px; padding: 14px;
            font-weight: 800; font-size: 16px; min-height: 50px; box-shadow: 0 10px 22px rgba(210,98,42,.25); }
        .go:disabled { opacity: .45; box-shadow: none; cursor: not-allowed; }
        .cart-empty { color: var(--muted); font-size: 14px; text-align: center; padding: 6px 0 2px; }

        .toast { position: fixed; left: 50%; top: calc(70px + env(safe-area-inset-top, 0px)); transform: translate(-50%, -20px); opacity: 0; z-index: 9;
            background: var(--ok); color: #fff; padding: 11px 16px; border-radius: 12px; font-weight: 700; font-size: 14px; transition: all .25s ease;
            width: max-content; max-width: calc(100% - 32px); box-shadow: 0 14px 30px rgba(6,46,36,.25); pointer-events: none; }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }
        .toast.bad { background: var(--danger); }
    </style>
</head>
<body>
    <header>
        <a href="/comptoir" aria-label="Retour au comptoir">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
        </a>
        <div>
            <h1>Vente de secours</h1>
            <div class="who" id="who"></div>
        </div>
        <span class="net off" id="net">Hors ligne</span>
    </header>

    <main>
        <div id="nocat" class="note bad" hidden>
            <strong>La vente de secours n'est pas prête sur ce téléphone.</strong><br>
            Ouvrez une fois le comptoir avec internet, connecté avec votre compte de caisse : les produits et les prix seront copiés dans le téléphone.
        </div>

        <div id="ready" hidden>
            <div class="note info" id="intro"></div>
            <div class="note warn" id="closed" hidden>
                <strong>La caisse était fermée</strong> à la dernière connexion. Les ventes partiront quand la caisse sera ouverte.
            </div>

            <div class="cats" id="cats" role="tablist" aria-label="Catégories"></div>
            <div class="grid" id="grid"></div>

            <h2>Ventes gardées dans le téléphone <span class="count" id="pcount">0</span></h2>
            <div class="list" id="pending"></div>
            <button type="button" class="send" id="send" hidden>Envoyer maintenant</button>

            <h2 id="sentTitle" hidden>Déjà envoyées</h2>
            <div class="list" id="sent" hidden></div>
        </div>
    </main>

    <div class="cart" id="cart" hidden>
        <div class="cart-in">
            <div class="cart-empty" id="cartEmpty">Touchez les produits vendus.</div>
            <div id="cartFull" hidden>
                <div class="lines" id="lines"></div>
                <div class="tot"><span>Total</span><strong id="total">0 F</strong></div>
                <div class="bills" id="bills" aria-label="Billet donné par le client"></div>
                <div class="cashrow">
                    <input id="cash" type="text" inputmode="numeric" autocomplete="off" placeholder="Espèces reçues" aria-label="Espèces reçues">
                    <div class="change" id="change"></div>
                </div>
                <button type="button" class="go" id="go" disabled>Valider la vente</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script src="/js/offline-sales.js"></script>
    <script>
    (function () {
        var ME = window.MEOffline;
        var $ = function (id) { return document.getElementById(id); };
        var fcfa = function (n) { return String(Math.round(n || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' F'; };
        var esc = function (s) { var d = document.createElement('div'); d.textContent = String(s == null ? '' : s); return d.innerHTML; };
        var hour = function (iso) { var d = new Date(iso); return ('0' + d.getHours()).slice(-2) + 'h' + ('0' + d.getMinutes()).slice(-2); };

        var cart = {};          // id produit -> quantité
        var products = {};      // id -> { id, name, price }
        var activeCat = 0;
        var cash = 0;
        var catalog = null;

        function toast(msg, bad) {
            var t = $('toast');
            t.textContent = msg;
            t.className = 'toast show' + (bad ? ' bad' : '');
            clearTimeout(toast.t);
            toast.t = setTimeout(function () { t.className = 'toast' + (bad ? ' bad' : ''); }, 2600);
        }

        function setNet() {
            var on = navigator.onLine !== false;
            $('net').textContent = on ? 'En ligne' : 'Hors ligne';
            $('net').className = 'net ' + (on ? 'on' : 'off');
        }

        function total() {
            var t = 0;
            Object.keys(cart).forEach(function (id) { t += products[id].price * cart[id]; });
            return t;
        }

        function boot() {
            catalog = ME && ME.canSell() ? ME.catalog() : null;
            if (!catalog || !catalog.categories || !catalog.categories.length) {
                $('nocat').hidden = false;
                return;
            }

            $('ready').hidden = false;
            $('cart').hidden = false;
            $('who').textContent = (catalog.restaurant.name || '') + ' · ' + (catalog.user.name || '');
            $('intro').innerHTML = '<strong>Sans internet, vendez ici en espèces.</strong> Les ventes restent dans ce téléphone et partent toutes seules quand internet revient. Prix copiés à ' + hour(catalog.generated_at) + '.';
            $('closed').hidden = !!catalog.register_open;

            catalog.categories.forEach(function (c) { c.products.forEach(function (p) { products[p.id] = p; }); });
            renderCats();
            renderGrid();
            renderCart();
            renderPending();
        }

        function renderCats() {
            $('cats').innerHTML = catalog.categories.map(function (c, i) {
                return '<button type="button" role="tab" class="cat' + (i === activeCat ? ' on' : '') + '" data-cat="' + i + '">' + esc(c.name) + '</button>';
            }).join('');
        }

        function renderGrid() {
            var cat = catalog.categories[activeCat];
            $('grid').innerHTML = cat.products.map(function (p) {
                var q = cart[p.id] || 0;
                return '<button type="button" class="prod" data-id="' + p.id + '">'
                    + (q ? '<span class="q">' + q + '</span>' : '')
                    + '<span class="n">' + esc(p.name) + '</span><span class="p">' + fcfa(p.price) + '</span></button>';
            }).join('');
        }

        function renderCart() {
            var ids = Object.keys(cart);
            $('cartEmpty').hidden = ids.length > 0;
            $('cartFull').hidden = ids.length === 0;
            document.body.style.paddingBottom = ids.length ? '360px' : '120px';
            if (!ids.length) return;

            $('lines').innerHTML = ids.map(function (id) {
                var p = products[id];
                return '<div class="line"><span class="ln">' + esc(p.name) + '</span>'
                    + '<button type="button" class="step" data-minus="' + id + '" aria-label="Un de moins">−</button>'
                    + '<span class="lq">' + cart[id] + '</span>'
                    + '<button type="button" class="step" data-plus="' + id + '" aria-label="Un de plus">+</button>'
                    + '<span class="lt">' + fcfa(p.price * cart[id]) + '</span></div>';
            }).join('');

            var t = total();
            $('total').textContent = fcfa(t);

            var bills = [t].concat([1000, 2000, 5000, 10000].filter(function (b) { return b > t; }).slice(0, 3));
            $('bills').innerHTML = bills.map(function (b, i) {
                return '<button type="button" class="bill' + (cash === b ? ' on' : '') + '" data-bill="' + b + '">' + (i === 0 ? 'Compte juste' : fcfa(b).replace(' F', '')) + '</button>';
            }).join('');

            renderChange();
        }

        function renderChange() {
            var t = total();
            var el = $('change');
            if (!cash) { el.className = 'change'; el.innerHTML = 'Monnaie<strong>…</strong>'; }
            else if (cash < t) { el.className = 'change miss'; el.innerHTML = 'Il manque<strong>' + fcfa(t - cash) + '</strong>'; }
            else { el.className = 'change ok'; el.innerHTML = 'Monnaie à rendre<strong>' + fcfa(cash - t) + '</strong>'; }
            $('go').disabled = !(t > 0 && cash >= t);
            $('go').textContent = t > 0 && cash >= t ? 'Valider la vente · ' + fcfa(t) : 'Valider la vente';
        }

        function setCash(n, fromInput) {
            cash = Math.max(0, Math.min(10000000, parseInt(n, 10) || 0));
            if (!fromInput) $('cash').value = cash ? fcfa(cash).replace(' F', '') : '';
            renderCart();
        }

        function renderPending() {
            var q = ME.queue();
            $('pcount').textContent = q.length;
            $('send').hidden = !q.some(function (s) { return !s.rejected; }) || navigator.onLine === false;

            $('pending').innerHTML = q.length ? q.slice().reverse().map(function (s) {
                var what = s.lines.map(function (l) { return l.quantity + ' × ' + esc(l.name); }).join(', ');
                if (s.rejected) {
                    return '<div class="row bad"><div class="main"><div>' + hour(s.sold_at) + ' · ' + what + '</div><div>Refusée : ' + esc(s.rejected) + '</div></div>'
                        + '<span class="amt">' + fcfa(s.total) + '</span><button type="button" data-remove="' + s.uuid + '">Retirer</button></div>';
                }
                return '<div class="row"><div class="main"><div>' + hour(s.sold_at) + ' · ' + what + '</div><div>En attente d\'internet · reçu ' + fcfa(s.cash_received) + ', rendu ' + fcfa(s.cash_received - s.total) + '</div></div>'
                    + '<span class="amt">' + fcfa(s.total) + '</span></div>';
            }).join('') : '<div class="empty">Aucune vente en attente.</div>';

            var sent = ME.sent();
            $('sentTitle').hidden = $('sent').hidden = !sent.length;
            $('sent').innerHTML = sent.slice(0, 10).map(function (s) {
                return '<div class="row"><div class="main"><div>Vente de ' + hour(s.sold_at) + '</div><div>Enregistrée ' + esc(s.order_number) + ' à ' + hour(s.sent_at) + '</div></div><span class="amt">' + fcfa(s.total) + '</span></div>';
            }).join('');
        }

        function validate() {
            var t = total();
            if (!(t > 0 && cash >= t)) return;

            var lines = Object.keys(cart).map(function (id) { return { product_id: +id, quantity: cart[id], name: products[id].name, price: products[id].price }; });
            var ok = ME.addSale({
                uuid: ME.uuid(),
                sold_at: new Date().toISOString(),
                total: t,
                cash_received: cash,
                items: lines.map(function (l) { return { product_id: l.product_id, quantity: l.quantity }; }),
                lines: lines
            });

            if (!ok) { toast('Téléphone plein : impossible de garder la vente.', true); return; }

            toast('Vente gardée · monnaie ' + fcfa(cash - t));
            cart = {};
            setCash(0);
            renderGrid();
            renderPending();
            if (navigator.onLine !== false) ME.sync();
        }

        document.addEventListener('click', function (e) {
            var b = e.target.closest('button');
            if (!b) return;
            if (b.dataset.cat !== undefined) { activeCat = +b.dataset.cat; renderCats(); renderGrid(); return; }
            if (b.dataset.id) { var id = b.dataset.id; cart[id] = (cart[id] || 0) + 1; renderGrid(); renderCart(); return; }
            if (b.dataset.plus) { cart[b.dataset.plus]++; renderGrid(); renderCart(); return; }
            if (b.dataset.minus) { if (--cart[b.dataset.minus] <= 0) delete cart[b.dataset.minus]; renderGrid(); renderCart(); return; }
            if (b.dataset.bill) { setCash(b.dataset.bill); return; }
            if (b.dataset.remove) {
                var saleId = b.dataset.remove;
                window.meConfirm({ title: 'Retirer cette vente refusée ?', body: 'Elle ne sera pas enregistrée. Vérifiez d\'abord l\'argent avec le patron.', ok: 'Retirer la vente', danger: true })
                    .then(function (yes) { if (yes) { ME.removeSale(saleId); renderPending(); } });
                return;
            }
            if (b.id === 'go') validate();
            if (b.id === 'send') ME.sync();
        });

        $('cash').addEventListener('input', function () {
            var digits = this.value.replace(/\D/g, '');
            this.value = digits ? fcfa(+digits).replace(' F', '') : '';
            setCash(digits, true);
        });

        document.addEventListener('me-offline:synced', function (e) {
            var d = e.detail || {};
            if (d.saved) toast(d.saved + (d.saved > 1 ? ' ventes envoyées' : ' vente envoyée') + ' · ' + fcfa(d.total));
            else if (d.wait) toast(d.wait, true);
            else if (d.error === 'login') toast('Reconnectez-vous pour envoyer les ventes.', true);
            renderPending();
        });

        window.addEventListener('online', function () { setNet(); renderPending(); ME.refreshCatalog(); });
        window.addEventListener('offline', function () { setNet(); renderPending(); });

        setNet();
        boot();
        if (catalog && navigator.onLine !== false) ME.sync();
    })();
    </script>
@include('partials.confirm')
</body>
</html>
