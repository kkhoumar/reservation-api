@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ');
    $items = $order?->items ?? collect();
    $count = (int) $items->sum('quantity');
    $total = (int) ($order?->total ?? 0);
    $visibleCategories = $categories->filter(fn ($c) => $c->products->isNotEmpty());
@endphp

<div class="new-order" x-data="meOrder()" :class="{ 'has-items': count({{ $count }}) > 0 }">
    <style>
        [x-cloak] { display: none !important; }
        .new-order { --tap: cubic-bezier(.2, .9, .3, 1.4); }
        .new-order .no-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
        .new-order .no-head h1 { margin: 0; color: var(--color-primary); }
        .new-order .no-num { background: var(--color-mint, #EAF4F0); color: var(--color-primary); font-weight: 700; font-size: 13px; padding: 4px 10px; border-radius: 999px; }
        .new-order .no-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr); gap: 20px; align-items: start; }

        /* Catégories : bande collante, défilement horizontal */
        .new-order .cats { position: sticky; top: 64px; z-index: 20; display: flex; gap: 8px; overflow-x: auto; padding: 10px 2px; margin: -6px 0 6px;
            background: var(--color-bg); scrollbar-width: none; }
        .new-order .cats::-webkit-scrollbar { display: none; }
        .new-order .cats a { flex-shrink: 0; text-decoration: none; padding: 8px 14px; border-radius: 999px; font-size: 14px; font-weight: 600;
            background: var(--color-surface, #fff); color: var(--color-text); border: 1px solid var(--color-border); transition: all .15s ease; }
        .new-order .cats a.on { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
        .new-order .cat-title { display: flex; align-items: baseline; gap: 8px; margin: 14px 0 10px; scroll-margin-top: 130px; }
        .new-order .cat-title h3 { margin: 0; font-size: 15px; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .04em; }
        .new-order .cat-title span { font-size: 12px; color: var(--color-text-secondary); }

        /* Cartes produits : toute la carte se touche */
        .new-order .pgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 8px; }
        .new-order .pc { position: relative; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; min-height: 104px;
            text-align: left; padding: 14px; border-radius: 16px; cursor: pointer; font: inherit; color: var(--color-text);
            background: var(--color-surface, #fff); border: 1.5px solid var(--color-border); box-shadow: var(--shadow-sm);
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease, transform .12s ease; -webkit-user-select: none; user-select: none; }
        .new-order .pc:hover { border-color: var(--color-primary-300); }
        .new-order .pc:active:not(:disabled) { transform: scale(.96); }
        .new-order .pc.on { border-color: var(--color-primary); background: linear-gradient(180deg, #fff, var(--color-mint, #EAF4F0)); box-shadow: 0 6px 18px rgba(11,77,60,.14); }
        .new-order .pc .pn { font-weight: 700; font-size: 15px; line-height: 1.25; padding-right: 22px; }
        .new-order .pc .pp { display: flex; align-items: flex-end; justify-content: space-between; gap: 6px; }
        .new-order .pc .price { font-weight: 800; font-size: 15px; color: var(--color-accent); white-space: nowrap; }
        .new-order .pc .stock { font-size: 11px; font-weight: 700; color: var(--color-accent-dark, #A84A1C); background: var(--color-warning-bg, #FFF4E5); padding: 2px 7px; border-radius: 999px; white-space: nowrap; }
        .new-order .pc .plus { position: absolute; top: 10px; right: 10px; width: 24px; height: 24px; border-radius: 8px; display: grid; place-items: center;
            color: var(--color-primary); background: var(--color-mint, #EAF4F0); transition: opacity .15s ease; }
        .new-order .pc.on .plus { opacity: 0; }
        .new-order .pc .q { position: absolute; top: -9px; right: -9px; min-width: 30px; height: 30px; padding: 0 8px; border-radius: 999px;
            display: grid; place-items: center; font-weight: 800; font-size: 15px; color: #fff; background: var(--color-accent);
            border: 3px solid var(--color-bg); box-shadow: 0 4px 10px rgba(210,98,42,.35); }
        .new-order .pc.bump .q { animation: no-pop .32s var(--tap); }
        .new-order .pc.shake { animation: no-shake .38s ease; border-color: var(--color-danger); }
        .new-order .pc:disabled { cursor: not-allowed; opacity: .55; box-shadow: none; background: var(--color-bg); }
        .new-order .pc:disabled .price { color: var(--color-text-secondary); text-decoration: line-through; }
        .new-order .pc .out { font-size: 11px; font-weight: 700; color: var(--color-danger); background: var(--color-danger-bg); padding: 2px 7px; border-radius: 999px; }
        @keyframes no-pop { 0% { transform: scale(.6); } 60% { transform: scale(1.25); } 100% { transform: scale(1); } }
        @keyframes no-shake { 0%,100% { transform: translateX(0); } 20%,60% { transform: translateX(-6px); } 40%,80% { transform: translateX(6px); } }

        /* Commande en cours */
        .new-order .cart { position: sticky; top: 76px; padding: 0; overflow: hidden; scroll-margin-top: 70px; }
        .new-order .cart-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px 12px; border-bottom: 1px solid var(--color-border); }
        .new-order .cart-head h3 { margin: 0; font-size: 17px; }
        .new-order .cart-head .n { background: var(--color-primary); color: #fff; font-size: 12px; font-weight: 800; min-width: 24px; height: 24px; padding: 0 8px; border-radius: 999px; display: inline-grid; place-items: center; }
        .new-order .empty { padding: 30px 18px; text-align: center; color: var(--color-text-secondary); font-size: 14px; }
        .new-order .empty .ico { width: 54px; height: 54px; border-radius: 16px; margin: 0 auto 10px; display: grid; place-items: center; background: var(--color-mint, #EAF4F0); color: var(--color-primary); }
        .new-order .lines { padding: 4px 18px; }
        .new-order .ln { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 6px 10px; align-items: center; padding: 12px 0; border-bottom: 1px dashed var(--color-border); }
        .new-order .ln:last-child { border-bottom: none; }
        .new-order .ln .nm { font-weight: 700; font-size: 15px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .new-order .ln .sub { font-size: 12px; color: var(--color-text-secondary); display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .new-order .ln .tag { font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: var(--color-mint, #EAF4F0); color: var(--color-primary); }
        .new-order .ln .tag.gift { background: var(--color-warning-bg, #FFF4E5); color: var(--color-accent-dark, #A84A1C); }
        .new-order .ln .lt { font-weight: 800; font-size: 15px; text-align: right; white-space: nowrap; }
        .new-order .stepper { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; }
        .new-order .pill { display: inline-flex; align-items: center; background: var(--color-bg); border: 1px solid var(--color-border); border-radius: 999px; padding: 3px; }
        .new-order .pill button { width: 38px; height: 38px; border-radius: 999px; border: none; display: grid; place-items: center; cursor: pointer;
            background: #fff; color: var(--color-primary); box-shadow: var(--shadow-sm); transition: transform .1s ease, background .15s ease; }
        .new-order .pill button:active { transform: scale(.88); }
        .new-order .pill button.minus { color: var(--color-text); }
        .new-order .pill button.plusb { background: var(--color-primary); color: #fff; }
        .new-order .pill button:disabled { background: transparent; box-shadow: none; color: var(--color-text-secondary); cursor: not-allowed; }
        .new-order .pill .v { min-width: 40px; text-align: center; font-weight: 800; font-size: 17px; font-variant-numeric: tabular-nums; }
        .new-order .unit { font-size: 12px; color: var(--color-text-secondary); }
        .new-order .sum { padding: 14px 18px; background: var(--color-bg); border-top: 1px solid var(--color-border); }
        .new-order .sum .row { display: flex; justify-content: space-between; align-items: baseline; }
        .new-order .sum .tot { font-family: var(--font-display); font-weight: 800; font-size: 24px; color: var(--color-primary); font-variant-numeric: tabular-nums; }
        .new-order .sum .tot.bump { animation: no-pop .3s var(--tap); display: inline-block; }
        .new-order .acts { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
        .new-order .acts .btn { justify-content: center; }
        .new-order .acts .big { font-size: 16px; padding: 14px 18px; }

        /* Messages : bulle en bas, visible même en bas de la liste */
        .new-order .toast { position: fixed; left: 50%; transform: translateX(-50%); bottom: 96px; z-index: 60; max-width: min(92vw, 440px);
            padding: 12px 16px; border-radius: 14px; font-size: 14px; font-weight: 600; box-shadow: 0 12px 30px rgba(0,0,0,.22); }
        .new-order .toast.err { background: #3A1410; color: #fff; }
        .new-order .toast.ok { background: var(--color-primary-900, #062E24); color: #fff; }

        /* Barre du bas (téléphone) */
        .new-order .order-bar { display: none; }
        @media (max-width: 860px) {
            .new-order .no-grid { grid-template-columns: minmax(0, 1fr); }
            .new-order .cart { position: static; }
            .new-order .cats { top: 56px; }
            .new-order .pgrid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .new-order .pc { min-height: 96px; padding: 12px; }
            .new-order.has-items { padding-bottom: 92px; }
            .new-order .order-bar {
                display: flex; align-items: center; gap: 12px;
                position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
                background: rgba(255,255,255,.96); backdrop-filter: blur(8px); border-top: 1px solid var(--color-border);
                box-shadow: 0 -8px 24px rgba(6,46,36,.10);
                padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
            }
            .new-order .order-bar .cnt { width: 44px; height: 44px; border-radius: 14px; background: var(--color-primary); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 17px; flex-shrink: 0; }
            .new-order .order-bar .cnt.bump { animation: no-pop .3s var(--tap); }
            .new-order .order-bar small { display: block; font-size: 12px; color: var(--color-text-secondary); }
            .new-order .order-bar strong { font-size: 19px; font-family: var(--font-display); font-variant-numeric: tabular-nums; }
            .new-order .order-bar .go { margin-left: auto; text-decoration: none; padding: 12px 16px; font-size: 15px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .new-order .pc.bump .q, .new-order .pc.shake, .new-order .tot.bump, .new-order .cnt.bump { animation: none; }
        }
    </style>

    {{-- Messages du serveur (stock épuisé, commande déjà encaissée…) --}}
    @if (session('error') || $errors->any())
        <div class="toast err" role="alert" wire:key="err-{{ uniqid() }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show" x-transition.opacity @click="show = false">
            {{ session('error') ?: $errors->first() }}
        </div>
    @endif
    @if (session('success'))
        <div class="toast ok" role="status" wire:key="ok-{{ uniqid() }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3500)" x-show="show" x-transition.opacity @click="show = false">
            {{ session('success') }}
        </div>
    @endif
    {{-- Message immédiat du téléphone (plus de stock) --}}
    <div class="toast err" role="alert" x-cloak x-show="msg" x-transition.opacity x-text="msg" @click="msg = ''"></div>

    <div class="no-head">
        <h1>
            @if ($tableId)
                {{ \App\Models\RestaurantTable::find($tableId)?->label }}
            @else
                À emporter
            @endif
        </h1>
        @if ($order)
            <span class="no-num">{{ $order->order_number }}</span>
        @endif
    </div>

    <div class="no-grid">

        {{-- Produits --}}
        <div>
            @if ($visibleCategories->count() > 1)
                <nav class="cats" aria-label="Catégories">
                    @foreach ($visibleCategories as $category)
                        <a href="#cat-{{ $category->id }}" :class="{ on: cat === {{ $category->id }} }" @click="cat = {{ $category->id }}">{{ $category->name }}</a>
                    @endforeach
                </nav>
            @endif

            @foreach ($visibleCategories as $category)
                <div class="cat-title" id="cat-{{ $category->id }}">
                    <h3>{{ $category->name }}</h3>
                    <span>{{ $category->products->count() }}</span>
                </div>
                <div class="pgrid">
                    @foreach ($category->products as $product)
                        @php
                            $q = (int) ($inOrder[$product->id] ?? 0);
                            $left = max(0, (int) $product->stock_quantity);
                        @endphp
                        @if ($product->stock_quantity <= 0)
                            <button type="button" class="pc" disabled wire:key="pc-{{ $product->id }}-{{ $q }}-0">
                                @if ($q)<span class="q">{{ $q }}</span>@endif
                                <span class="pn">{{ $product->name }}</span>
                                <span class="pp"><span class="price">{{ $fcfa($product->selling_price) }} F</span><span class="out">Épuisé</span></span>
                            </button>
                        @else
                            <button type="button" class="pc" wire:key="pc-{{ $product->id }}-{{ $q }}-{{ $left }}"
                                    :class="{ on: count({{ $q }}, {{ $product->id }}) > 0 }"
                                    @click="add({{ $product->id }}, {{ (int) $product->selling_price }}, {{ $left }}, $el, @js($product->name))"
                                    aria-label="Ajouter {{ $product->name }}">
                                <span class="plus" aria-hidden="true"><x-icon name="plus" size="14" stroke="2.6" /></span>
                                <span class="q" x-cloak x-show="count({{ $q }}, {{ $product->id }}) > 0" x-text="count({{ $q }}, {{ $product->id }})">{{ $q }}</span>
                                <span class="pn">{{ $product->name }}</span>
                                <span class="pp">
                                    <span class="price">{{ $fcfa($product->selling_price) }} F</span>
                                    @if ($product->isLowStock())
                                        <span class="stock">Plus que {{ $left }}</span>
                                    @endif
                                </span>
                            </button>
                        @endif
                    @endforeach
                </div>
            @endforeach

            @if ($visibleCategories->isEmpty())
                <div class="card empty">Aucun produit disponible. Ajoutez des produits dans « Produits ».</div>
            @endif
        </div>

        {{-- Commande en cours --}}
        <div class="card cart" id="panier">
            <div class="cart-head">
                <h3>Commande en cours</h3>
                <span class="n" x-cloak x-show="count({{ $count }}) > 0" x-text="count({{ $count }})" wire:key="cn-{{ $count }}">{{ $count }}</span>
            </div>

            @if ($items->isEmpty())
                <div class="empty" x-show="count(0) === 0">
                    <div class="ico"><x-icon name="utensils" size="26" /></div>
                    Touchez un produit pour l'ajouter.<br>Chaque touche ajoute 1.
                </div>
                <div class="empty" x-cloak x-show="count(0) > 0">Ajout en cours…</div>
            @else
                <div class="lines">
                    @foreach ($items as $item)
                        @php
                            $unit = intdiv((int) $item->total, max(1, (int) $item->quantity));
                            $merge = ! $item->isSent() && ! $item->note && ! $item->is_offered && (int) $item->unit_price === (int) $item->product?->selling_price;
                            $locked = $item->isSent() && ! $canTrimSent;
                            $qExpr = $item->quantity . ($merge ? ' + (pend[' . $item->product_id . '] || 0)' : '') . ' - (rm[' . $item->id . '] || 0)';
                        @endphp
                        <div class="ln" wire:key="ln-{{ $item->id }}-{{ $item->quantity }}" x-show="{{ $qExpr }} > 0">
                            <div style="min-width: 0;">
                                <div class="nm">{{ $item->product->name }}</div>
                                <div class="sub">
                                    <span class="unit">{{ $fcfa($item->unit_price) }} F l'unité</span>
                                    @if ($item->isSent())<span class="tag">En cuisine</span>@endif
                                    @if ($item->is_offered)<span class="tag gift">Offert</span>@endif
                                    @if ($item->note)<span>· {{ $item->note }}</span>@endif
                                </div>
                            </div>
                            <div class="lt" x-text="fmt(({{ $qExpr }}) * {{ $unit }}) + ' F'">{{ $fcfa($item->total) }} F</div>
                            <div class="stepper">
                                <div class="pill">
                                    @if ($locked)
                                        <button type="button" class="minus" disabled aria-label="Déjà en cuisine : seul le comptoir peut retirer" title="Déjà en cuisine : seul le comptoir peut retirer"><x-icon name="lock" size="16" /></button>
                                    @else
                                        <button type="button" class="minus" @click="minus({{ $item->id }}, {{ $item->product_id }}, {{ $unit }})" aria-label="Un {{ $item->product->name }} de moins"><x-icon name="minus" size="18" stroke="2.6" /></button>
                                    @endif
                                    <span class="v" x-text="{{ $qExpr }}">{{ $item->quantity }}</span>
                                    <button type="button" class="plusb" @click="addLine({{ $item->id }}, {{ $item->product_id }}, {{ (int) ($item->product?->selling_price ?? $item->unit_price) }}, {{ max(0, (int) ($item->product?->stock_quantity ?? 0)) }}, @js($item->product->name))" aria-label="Un {{ $item->product->name }} de plus"><x-icon name="plus" size="18" stroke="2.6" /></button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($order && $items->isNotEmpty())
                <div class="sum">
                    <div class="row">
                        <span style="font-weight: 700;">Total</span>
                        <span class="tot" x-ref="tot" x-text="fmt(total({{ $total }})) + ' FCFA'" wire:key="tot-{{ $total }}">{{ $fcfa($total) }} FCFA</span>
                    </div>

                    {{-- Les dernières touches partent avant l'envoi de la commande --}}
                    <div class="acts" @pointerdown.capture="flush()" @keydown.capture="flush()">
                    @if ($paysBefore)
                        {{-- Paiement avant service : on encaisse, puis on sert --}}
                        @if ($canCollect)
                            {{-- Patron, gérante, caissière : ils encaissent eux-mêmes --}}
                            <button wire:click="goToPayment" class="btn btn-primary big">Encaisser</button>
                            @if ($hasServers)
                                <button wire:click="sendToCounter" class="btn btn-ghost">Envoyer au comptoir</button>
                            @endif
                        @else
                            <button wire:click="sendToCounter" class="btn btn-primary big">Envoyer au comptoir</button>
                            <div style="font-size: 12px; color: var(--color-text-secondary); text-align: center;">
                                Apportez l'argent au comptoir. La commande est servie une fois encaissée.
                            </div>
                        @endif
                    @else
                        @if ($order->status === 'created')
                            <button wire:click="sendToKitchen" class="btn btn-secondary">Envoyer en cuisine</button>
                        @elseif ($order->status === 'in_preparation')
                            <button wire:click="markServed" class="btn btn-secondary">Marquer servie</button>
                        @endif

                        @if (in_array($order->status, ['served', 'in_preparation', 'created']))
                            <button wire:click="goToPayment" class="btn btn-primary big">Passer au paiement</button>
                        @endif
                    @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Téléphone : nombre d'articles et total toujours visibles en bas --}}
    <div class="order-bar" x-cloak x-show="count({{ $count }}) > 0">
        <span class="cnt" x-ref="cnt" x-text="count({{ $count }})" wire:key="bc-{{ $count }}">{{ $count }}</span>
        <div>
            <small>Commande en cours</small>
            <strong x-text="fmt(total({{ $total }})) + ' FCFA'" wire:key="bt-{{ $total }}">{{ $fcfa($total) }} FCFA</strong>
        </div>
        <a href="#panier" class="btn btn-primary go">Voir ↓</a>
    </div>

    <script>
        /*
         * Prise de commande au doigt : chaque touche ajoute 1, tout de suite à l'écran.
         * Le serveur enregistre derrière (stock, prix, total) ; s'il refuse, l'écran
         * revient à la vraie valeur et un message explique pourquoi.
         */
        window.meOrder = window.meOrder || function () {
            return {
                pend: {},   // ajouts en route, par produit
                rmp: {},    // retraits en route, par produit
                rm: {},     // retraits en route, par ligne
                amt: 0,     // montant en route
                nPend: 0,   // articles en route (+ ajouts, − retraits)
                toAdd: {},  // touches pas encore parties, par produit
                toCut: {},  // « − » pas encore partis, par ligne
                flushTimer: null,
                seq: 0,
                cat: null,
                msg: '',
                msgTimer: null,

                count(serverQty, productId) {
                    if (productId === undefined) return serverQty + this.nPend;
                    return serverQty + (this.pend[productId] || 0) - (this.rmp[productId] || 0);
                },
                total(serverTotal) { return serverTotal + this.amt; },
                fmt(v) { return String(Math.max(0, Math.round(v))).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); },

                buzz(ms) { try { navigator.vibrate && navigator.vibrate(ms || 8); } catch (e) {} },
                bump(el) {
                    if (!el) return;
                    el.classList.remove('bump'); void el.offsetWidth; el.classList.add('bump');
                },
                bumpTotals() {
                    this.$nextTick(() => {
                        this.bump(this.$refs.tot);
                        this.bump(this.$refs.cnt);
                    });
                },
                say(text) {
                    this.msg = text;
                    clearTimeout(this.msgTimer);
                    this.msgTimer = setTimeout(() => { this.msg = ''; }, 3500);
                },
                shift(map, key, by) {
                    map[key] = (map[key] || 0) + by;
                    if (map[key] === 0) delete map[key];
                },

                // Touche d'une carte produit (ou du « + » d'une ligne)
                add(productId, price, left, el, name) {
                    if ((this.pend[productId] || 0) >= left) {
                        if (el) { el.classList.remove('shake'); void el.offsetWidth; el.classList.add('shake'); }
                        this.buzz([30, 40, 30]);
                        this.say('Plus de stock : ' + name + '.');
                        return;
                    }
                    this.shift(this.pend, productId, 1);
                    this.amt += price; this.nPend += 1;
                    this.buzz(8); this.bump(el); this.bumpTotals();

                    const b = this.toAdd[productId] || (this.toAdd[productId] = { n: 0, price: price });
                    b.n += 1;
                    this.later();
                },

                // Les touches rapides partent ensemble : un envoi par produit, pas un par touche
                later() {
                    clearTimeout(this.flushTimer);
                    this.flushTimer = setTimeout(() => this.flush(), 220);
                },
                flush() {
                    const adds = this.toAdd, cuts = this.toCut;
                    this.toAdd = {}; this.toCut = {};

                    Object.keys(adds).forEach((pid) => {
                        const { n, price } = adds[pid];
                        const done = () => { this.shift(this.pend, pid, -n); this.amt -= price * n; this.nPend -= n; };
                        this.$wire.addProduct(+pid, n, ++this.seq + '-' + Date.now()).then(done, done);
                    });
                    Object.keys(cuts).forEach((itemId) => {
                        const { n, pid, unit } = cuts[itemId];
                        const done = () => { this.shift(this.rm, itemId, -n); this.shift(this.rmp, pid, -n); this.amt += unit * n; this.nPend += n; };
                        this.$wire.decrementItem(+itemId, n, ++this.seq + '-' + Date.now()).then(done, done);
                    });
                },

                addLine(itemId, productId, price, left, name) {
                    const card = this.$root.querySelector('[aria-label="Ajouter ' + CSS.escape(name) + '"]');
                    this.add(productId, price, left, card, name);
                },

                // « − » d'une ligne
                minus(itemId, productId, unit) {
                    this.shift(this.rm, itemId, 1); this.shift(this.rmp, productId, 1);
                    this.amt -= unit; this.nPend -= 1;
                    this.buzz(6); this.bumpTotals();

                    const c = this.toCut[itemId] || (this.toCut[itemId] = { n: 0, pid: productId, unit: unit });
                    c.n += 1;
                    this.later();
                },
            };
        };
    </script>
</div>
