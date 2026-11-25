@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $readOnly = \App\Support\ReadOnlyGuard::isReadOnly();
    $kinds = \App\Models\Category::KINDS;
    $byKind = $products->groupBy(fn ($p) => $p->category?->kind ?? 'food');
    $countTone = $countDays === null || $countDays > \App\Models\StockCount::REMIND_AFTER_DAYS ? 'danger' : ($countDays > 3 ? 'warn' : 'ok');
    $countAgo = match (true) {
        $countDays === null => 'Jamais',
        $countDays === 0 => "Aujourd'hui",
        $countDays === 1 => 'Hier',
        default => "Il y a {$countDays} jours",
    };
    $tabIcons = ['overview' => 'box', 'delivery' => 'truck', 'loss' => 'alert', 'history' => 'history'];
    $typeStyle = [
        'initial' => ['box', 'var(--color-text-secondary)', 'var(--color-bg)'],
        'delivery' => ['truck', 'var(--color-success)', 'var(--color-success-bg)'],
        'loss' => ['alert', 'var(--color-danger)', 'var(--color-danger-bg)'],
        'inventory' => ['clipboard', '#2463B5', '#EAF1FB'],
    ];
@endphp

<div class="sc st">
    @include('partials.stock-count-styles')
    <style>
        .st { padding-bottom: 24px; }
        .st-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 18px 0 14px; }
        .st-kpi { background: #fff; border: 1px solid var(--color-border); border-radius: 16px; padding: 14px 16px; min-width: 0; color: inherit; text-decoration: none; display: block; }
        .st-kpi .k { font-size: 12.5px; color: var(--color-text-secondary); display: flex; align-items: center; gap: 6px; font-weight: 600; }
        .st-kpi .v { font-size: 23px; font-weight: 800; letter-spacing: -.01em; margin-top: 4px; white-space: nowrap; }
        .st-kpi .v small { font-size: 13px; font-weight: 700; color: var(--color-text-secondary); }
        .st-kpi .s { font-size: 12.5px; color: var(--color-text-secondary); margin-top: 2px; }
        .st-kpi.hl { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
        .st-kpi.hl .k, .st-kpi.hl .s, .st-kpi.hl .v small { color: rgba(255,255,255,.75); }
        a.st-kpi:hover { border-color: var(--color-primary-300); }
        .st-alerts { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .st-alert { display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; padding: 8px 12px; border-radius: 12px; text-decoration: none; }
        .st-alert.red { background: var(--color-danger-bg); color: var(--color-danger); }
        .st-alert.orange { background: var(--color-warning-bg); color: #8A5A12; }

        .st-tabs { display: flex; gap: 4px; background: #fff; border: 1px solid var(--color-border); border-radius: 14px; padding: 4px; margin-bottom: 16px; overflow-x: auto; scrollbar-width: none; }
        .st-tabs::-webkit-scrollbar { display: none; }
        .st-tabs button { flex: 1 0 auto; border: 0; background: none; font: inherit; font-size: 14px; font-weight: 600; color: var(--color-text-secondary); padding: 9px 14px; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 7px; white-space: nowrap; }
        .st-tabs button.on { background: var(--color-primary); color: #fff; }

        /* Liste des produits */
        .st-row { display: grid; grid-template-columns: minmax(0, 1fr) 110px 110px 110px 120px; gap: 6px 14px; align-items: center; padding: 13px 18px; border-top: 1px solid var(--color-border); }
        .st-row:first-child { border-top: 0; }
        .st-row.head { font-size: 11.5px; font-weight: 700; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .03em; padding-top: 10px; padding-bottom: 10px; background: var(--color-bg); }
        .st-row > * { min-width: 0; }
        .st-cell { text-align: right; font-size: 14.5px; }
        .st-cell small { display: none; font-size: 11px; font-weight: 600; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .03em; }
        .st-qty { font-size: 18px; font-weight: 800; }
        .st-qty.low { color: var(--color-danger); }
        .st-low { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 700; color: var(--color-danger); background: var(--color-danger-bg); padding: 2px 8px; border-radius: 6px; margin-left: 6px; vertical-align: 2px; }
        .st-missing { color: var(--color-warning); font-weight: 700; font-size: 13px; }
        .st-gauge { display: block; height: 5px; border-radius: 99px; background: var(--color-border); overflow: hidden; margin: 6px 0 0 auto; width: 64px; }
        .st-gauge i { display: block; height: 100%; border-radius: 99px; }
        .st-gauge i.ok { background: var(--color-primary-300); }
        .st-gauge i.mid { background: #E3A23B; }
        .st-gauge i.low { background: var(--color-danger); }
        .st-kpi .k svg { width: 26px; height: 26px; padding: 5px; border-radius: 8px; background: var(--color-bg); color: var(--color-primary); }
        .st-kpi.hl .k svg { background: rgba(255,255,255,.14); color: #fff; }

        /* Formulaires */
        .st-form { max-width: 760px; }
        .st-field { margin-bottom: 14px; }
        .st-field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .st-field label span { font-weight: 500; color: var(--color-text-secondary); }
        .st-field input, .st-field select { width: 100%; }
        .st-line { display: grid; grid-template-columns: minmax(0, 1fr) 96px 140px; gap: 10px; align-items: center; padding: 10px 18px; border-top: 1px solid var(--color-border); }
        .st-line:first-child { border-top: 0; }
        .st-line input { width: 100%; text-align: right; font-weight: 700; }
        .st-line .stock { font-size: 12.5px; color: var(--color-text-secondary); }
        .st-m { display: none; font-size: 11px; font-weight: 700; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .03em; margin-bottom: 4px; }
        .st-toggle { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border: 1px solid var(--color-border); border-radius: 14px; background: #fff; cursor: pointer; margin: 14px 0; }
        .st-toggle input { margin-top: 3px; }
        .st-total { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; background: var(--color-mint); border-radius: 14px; padding: 12px 16px; margin-top: 12px; font-size: 14px; }
        .st-total strong { font-size: 18px; color: var(--color-primary); }
        .st-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .st-photo-btn { width: 100%; border: 2px dashed var(--color-border); background: var(--color-bg); border-radius: 14px; padding: 18px 14px; display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; font: inherit; font-weight: 700; color: var(--color-text); }
        .st-photo-btn small { font-weight: 500; color: var(--color-text-secondary); font-size: 12.5px; }
        .st-photo-btn:hover { border-color: var(--color-primary-300); }
        .st-photo-preview { display: flex; align-items: center; gap: 12px; }
        .st-photo-preview img { width: 96px; height: 96px; object-fit: cover; border-radius: 12px; border: 1px solid var(--color-border); }
        .st-thumb { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12.5px; font-weight: 700; color: var(--color-accent); text-decoration: none; }
        .st-thumb img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid var(--color-border); }
        .st-empties { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 700; color: #2463B5; background: #E6F0FA; padding: 2px 8px; border-radius: 6px; margin-left: 6px; vertical-align: 2px; }

        /* Historique */
        .st-move { display: grid; grid-template-columns: 38px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 12px 18px; border-top: 1px solid var(--color-border); }
        .st-move:first-child { border-top: 0; }
        .st-move .ic { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; }
        .st-move .t { font-weight: 700; font-size: 14.5px; }
        .st-move .d { font-size: 12.5px; color: var(--color-text-secondary); margin-top: 2px; line-height: 1.45; }
        .st-move .q { font-weight: 800; font-size: 17px; white-space: nowrap; text-align: right; }

        @media (max-width: 980px) { .st-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 760px) {
            .st-row { grid-template-columns: repeat(3, minmax(0, 1fr)); padding: 14px; gap: 8px; }
            .st-row.head { display: none; }
            .st-row .sc-info { grid-column: 1 / -1; }
            .st-cell { text-align: left; background: var(--color-bg); border-radius: 10px; padding: 7px 10px; }
            .st-cell small { display: block; }
            .st-gauge { margin-left: 0; width: 100%; }
            .st-cell.qtycell { order: -1; }
            .st-row .sc-info { order: -2; }
            .st-cell.value { display: none; }
            .st-line { grid-template-columns: 1fr 1fr; padding: 12px 14px; }
            .st-line > :first-child { grid-column: 1 / -1; }
            .st-line.st-head { display: none; }
            .st-m { display: block; }
            .st .sc-seg { flex-wrap: wrap; }
            .st-move { padding: 12px 14px; gap: 10px; }
        }
        @media (max-width: 520px) {
            .st-tabs { display: grid; grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 420px) {
            .st-kpi .v { font-size: 20px; }
            .st-kpi .k svg { display: none; }
            .st-kpi .k { font-size: 12px; }
            .st-kpi { padding: 12px 13px; }
        }
    </style>

    <x-flash />

    @if (session('empties_alerts'))
        <div class="sc-warn" style="background: var(--color-danger-bg); color: var(--color-danger);">
            <x-icon name="alert" size="18" style="margin-top: 1px;" />
            <div>
                @foreach (session('empties_alerts') as $alert)
                    <strong>Le dépôt a repris {{ $alert['surplus'] }} vides de {{ $alert['product'] }} de plus que prévu.</strong>
                    Des bières ont peut-être été vendues sans bon ({{ $fcfa($alert['value']) }} F). Vérifiez avec votre équipe.<br>
                @endforeach
                <span style="font-weight: 500;">C'est noté dans le Journal.</span>
            </div>
        </div>
    @endif

    {{-- ================= En-tête ================= --}}
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px;">
        <div>
            <div class="eyebrow">Boissons et nourriture</div>
            <h1>Stock</h1>
            <p class="lead">Chaque vente, livraison, perte ou comptage change le stock. L'application garde la trace de chacun.</p>
        </div>
        @unless ($readOnly)
            <a href="{{ route('stock.count') }}" class="btn btn-primary"><x-icon name="clipboard" size="17" /> Compter le stock</a>
        @endunless
    </div>

    {{-- ================= Chiffres clés ================= --}}
    <div class="st-kpis">
        <div class="st-kpi hl">
            <div class="k"><x-icon name="tag" size="15" /> Au prix de vente</div>
            <div class="v">{{ $fcfa($stockSaleValue) }} <small>F</small></div>
            <div class="s">si tout le stock est vendu</div>
        </div>
        <div class="st-kpi">
            <div class="k"><x-icon name="wallet" size="15" /> Au prix d'achat</div>
            <div class="v">{{ $fcfa($stockValue) }} <small>F</small></div>
            <div class="s">l'argent placé dans le stock</div>
        </div>
        <div class="st-kpi">
            <div class="k"><x-icon name="chart" size="15" /> Bénéfice possible</div>
            <div class="v" style="color: var(--color-success);">{{ $fcfa($stockSaleValue - $stockValue) }} <small>F</small></div>
            <div class="s">vente moins achat</div>
        </div>
        <a href="{{ route('stock.count') }}" class="st-kpi">
            <div class="k"><x-icon name="calendar" size="15" /> Dernier comptage</div>
            <div class="v" style="color: {{ $countTone === 'danger' ? 'var(--color-danger)' : ($countTone === 'warn' ? 'var(--color-warning)' : 'inherit') }};">{{ $countAgo }}</div>
            <div class="s">{{ $countTone === 'danger' ? 'Comptez le frigo et les casiers' : 'Voir les comptages' }}</div>
        </a>
    </div>

    @if ($lowCount > 0 || $missingCost > 0)
        <div class="st-alerts">
            @if ($lowCount > 0)
                <span class="st-alert red">
                    <x-icon name="alert" size="16" /> {{ $lowCount }} {{ $lowCount > 1 ? 'produits bientôt en rupture' : 'produit bientôt en rupture' }}
                </span>
            @endif
            @if ($missingCost > 0)
                <a href="{{ route('products.manage') }}" class="st-alert orange">
                    <x-icon name="tag" size="16" /> {{ $missingCost }} {{ $missingCost > 1 ? 'prix d\'achat manquants' : 'prix d\'achat manquant' }}, à compléter
                </a>
            @endif
        </div>
    @endif

    {{-- ================= Onglets ================= --}}
    <div class="st-tabs" role="tablist">
        @foreach ($tabs as $key => $label)
            @continue($readOnly && in_array($key, ['delivery', 'loss'], true))
            <button type="button" role="tab" wire:click="setTab('{{ $key }}')" class="{{ $tab === $key ? 'on' : '' }}">
                <x-icon :name="$tabIcons[$key] ?? 'box'" size="16" /> {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ================= STOCK ================= --}}
    @if ($tab === 'overview')
        @if ($products->isEmpty())
            <div class="sc-card" style="text-align: center; padding: 32px 18px;">
                <x-icon name="box" size="32" style="color: var(--color-text-secondary);" />
                <p style="font-size: 15px; margin: 10px 0 14px;">Aucun produit pour le moment.</p>
                <a href="{{ route('products.manage') }}" class="btn btn-primary">Ajouter des produits</a>
            </div>
        @endif

        @foreach ($kinds as $k => $label)
            @continue(! $byKind->has($k))
            @php
                $group = $byKind[$k];
                $groupSale = $group->sum(fn ($p) => max(0, $p->stock_quantity) * $p->selling_price);
                $groupCost = $group->sum(fn ($p) => max(0, $p->stock_quantity) * $p->unitValue());
                $groupMax = (int) $group->max('stock_quantity');
            @endphp
            <section class="sc-section">
                <div class="sc-section-head">
                    <h2><span class="sc-h-ico {{ $k }}"><x-icon :name="$k === 'drink' ? 'bottle' : 'utensils'" size="17" /></span>{{ $label }}</h2>
                    <span class="muted small">{{ $group->count() }} {{ $group->count() > 1 ? 'produits' : 'produit' }}</span>
                </div>
                <div class="sc-list">
                    <div class="st-row head">
                        <span>Produit</span><span style="text-align: right;">En stock</span><span style="text-align: right;">Prix d'achat</span><span style="text-align: right;">Prix de vente</span><span style="text-align: right;">Valeur</span>
                    </div>
                    @foreach ($group as $p)
                        <div class="st-row">
                            <div class="sc-info with-ava">
                                <x-stock-ava :kind="$k" />
                                <div class="sc-txt">
                                <div class="sc-name">{{ $p->name }}@if ($p->category && mb_strtolower($p->category->name) !== mb_strtolower($label))<span class="cat">{{ $p->category->name }}</span>@endif@if ($p->isLowStock())<span class="st-low"><x-icon name="alert" size="12" stroke="2.4" /> Bientôt en rupture</span>@endif</div>
                                @if ($p->stock_alert_threshold !== null || $p->is_returnable)
                                    <div class="sc-calc">
                                        @if ($p->stock_alert_threshold !== null) Alerte à {{ $p->stock_alert_threshold }} @endif
                                        @if ($p->is_returnable)
                                            {{ $p->stock_alert_threshold !== null ? '·' : '' }} Vides rendus au dépôt{{ $p->crate_size ? ', casier de ' . $p->crate_size : '' }} ·
                                            {{ $p->hasEmptiesBaseline() ? $p->empty_quantity . ' vides attendus' : 'vides pas encore comptés' }}
                                        @endif
                                    </div>
                                @endif
                                </div>
                            </div>
                            <div class="st-cell qtycell"><small>En stock</small><span class="st-qty {{ $p->isLowStock() ? 'low' : '' }}">{{ $p->stock_quantity }}</span>@php $ref = max(1, ($p->stock_alert_threshold ?? 0) * 4, $groupMax); $lvl = $p->isLowStock() ? 'low' : ($p->stock_alert_threshold !== null && $p->stock_quantity <= $p->stock_alert_threshold * 2 ? 'mid' : 'ok'); @endphp<span class="st-gauge"><i class="{{ $lvl }}" style="width: {{ max(4, min(100, round(max(0, $p->stock_quantity) / $ref * 100))) }}%;"></i></span></div>
                            <div class="st-cell"><small>Achat</small>@if ($p->cost_price !== null){{ $fcfa($p->cost_price) }} F @else<span class="st-missing">Manquant</span>@endif</div>
                            <div class="st-cell"><small>Vente</small>{{ $fcfa($p->selling_price) }} F</div>
                            <div class="st-cell value"><strong>{{ $fcfa(max(0, $p->stock_quantity) * $p->selling_price) }} F</strong></div>
                        </div>
                    @endforeach
                    <div class="sc-sub">
                        <span>{{ $label }} à la vente : <strong>{{ $fcfa($groupSale) }} F</strong></span>
                        <span>Bénéfice possible : <strong style="color: var(--color-success);">{{ $fcfa($groupSale - $groupCost) }} F</strong></span>
                    </div>
                </div>
            </section>
        @endforeach

        @if ($recentCounts->isNotEmpty())
            <section class="sc-section">
                <div class="sc-section-head">
                    <h2>Derniers comptages</h2>
                    <a href="{{ route('stock.count') }}" class="small" style="color: var(--color-accent); font-weight: 700;">Tous les comptages</a>
                </div>
                <div class="sc-list sc-hist">
                    @foreach ($recentCounts as $c)
                        @php $d = $c->created_at->copy()->locale('fr'); $g = $c->gap_sale_value ?: $c->value_gap; @endphp
                        <a href="{{ route('stock.count.show', $c) }}">
                            <div style="min-width: 0;">
                                <div class="d">{{ ucfirst($d->isoFormat('dddd D MMMM')) }}</div>
                                <div class="small muted">{{ $d->format('H\hi') }} · {{ $c->products_counted }} {{ $c->products_counted > 1 ? 'produits' : 'produit' }} · par {{ $c->user?->first_name ?? 'inconnu' }}</div>
                            </div>
                            <div class="r" style="color: {{ $g < 0 ? 'var(--sc-miss)' : ($g > 0 ? 'var(--sc-over)' : 'var(--sc-ok)') }};">
                                {{ $g === 0 ? 'Juste' : ($g > 0 ? '+' : '−') . $fcfa(abs($g)) . ' F' }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    {{-- ================= LIVRAISON ================= --}}
    @if ($tab === 'delivery')
        <div class="st-form"
             x-data="{
                lines() { return Object.values(this.$wire.deliveryLines || {}); },
                qty() { return this.lines().reduce((s, l) => s + (parseInt(l.quantity) || 0), 0); },
                cost() { return this.lines().reduce((s, l) => s + (parseInt(l.quantity) || 0) * (parseInt(l.unit_cost) || 0), 0); },
                money(n) { return n.toLocaleString('fr-FR').replace(/ | /g, ' ') + ' F'; },
             }">
            <div class="sc-card" style="margin-bottom: 14px;">
                <h3><x-icon name="truck" size="16" /> Nouvelle livraison</h3>
                <p class="muted" style="font-size: 14px; margin: 0 0 14px; line-height: 1.5;">Remplissez seulement les produits livrés. Le prix d'achat saisi remplace l'ancien prix d'achat du produit.</p>
                <div class="st-field" style="margin-bottom: 0;">
                    <label for="supplier">Fournisseur <span>facultatif</span></label>
                    <input id="supplier" type="text" wire:model="supplier" maxlength="120" placeholder="Ex : Solibra, dépôt du quartier">
                </div>
            </div>

            @foreach ($kinds as $k => $label)
                @continue(! $byKind->has($k))
                <section class="sc-section" style="margin-bottom: 14px;">
                    <div class="sc-section-head"><h2><span class="sc-h-ico {{ $k }}"><x-icon :name="$k === 'drink' ? 'bottle' : 'utensils'" size="17" /></span>{{ $label }}</h2></div>
                    <div class="sc-list">
                        <div class="st-line st-head" style="font-size: 11.5px; font-weight: 700; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .03em; background: var(--color-bg);">
                            <span>Produit</span><span style="text-align: right;">Quantité</span><span class="cost" style="text-align: right;">Prix d'achat</span>
                        </div>
                        @foreach ($byKind[$k] as $p)
                            <div class="st-line">
                                <div class="sc-info with-ava">
                                    <x-stock-ava :kind="$k" />
                                    <div class="sc-txt">
                                        <div class="sc-name">{{ $p->name }}</div>
                                        <div class="stock">{{ $p->stock_quantity }} en stock @if ($p->is_returnable)· le dépôt reprend autant de vides @endif</div>
                                    </div>
                                </div>
                                <div><small class="st-m">Quantité</small><input type="number" min="0" inputmode="numeric" wire:model="deliveryLines.{{ $p->id }}.quantity" placeholder="0" aria-label="Quantité livrée : {{ $p->name }}"></div>
                                <div class="cost"><small class="st-m">Prix d'achat</small><x-money-input model="deliveryLines.{{ $p->id }}.unit_cost" :empty="''" placeholder="Prix d'achat" aria-label="Prix d'achat : {{ $p->name }}" style="width: 100%; text-align: right; font-weight: 700;" /></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
            @error('deliveryLines.*') <div class="sc-error">{{ $message }}</div> @enderror

            <label class="st-toggle">
                <input type="checkbox" wire:model.live="paidFromRegister">
                <span><strong>Payée avec l'argent de la caisse</strong>
                    <span class="muted" style="display: block; font-size: 13px; margin-top: 2px;">L'application crée la dépense « Achat de marchandises » dans la caisse ouverte.</span>
                </span>
            </label>

            @if ($paidFromRegister)
                @include('partials.photo-capture', ['model' => 'deliveryPhoto', 'label' => 'Photo du bon de livraison', 'hint' => 'Obligatoire : l\'argent sort de la caisse.'])
            @endif

            <div class="st-total">
                <span><strong x-text="qty()">0</strong> <span x-text="qty() > 1 ? 'articles livrés' : 'article livré'">article livré</span></span>
                <span>Total payé : <strong x-text="money(cost())">0 F</strong></span>
            </div>
            @error('delivery') <div class="sc-error">{{ $message }}</div> @enderror

            <button wire:click="saveDelivery" class="btn btn-primary" style="margin-top: 14px; height: 46px; padding: 0 22px;"><x-icon name="check-circle" size="18" /> Enregistrer la livraison</button>
        </div>
    @endif

    {{-- ================= PERTE OU CASSE ================= --}}
    @if ($tab === 'loss')
        <div class="sc-card" style="max-width: 520px;"
             x-data="{ costs: @js($products->mapWithKeys(fn ($p) => [$p->id => $p->unitValue()])) }">
            <h3><x-icon name="alert" size="16" /> Déclarer une perte</h3>
            <p class="muted" style="font-size: 14px; margin: 0 0 16px; line-height: 1.5;">Bouteille cassée, produit périmé ou renversé. La perte baisse le stock et apparaît dans le tableau de bord.</p>

            <div class="st-field">
                <label for="lossProduct">Produit</label>
                <select id="lossProduct" wire:model="lossProductId">
                    <option value="">Choisir un produit</option>
                    @foreach ($kinds as $k => $label)
                        @continue(! $byKind->has($k))
                        <optgroup label="{{ $label }}">
                            @foreach ($byKind[$k] as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}, {{ $p->stock_quantity }} en stock</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('lossProductId') <div class="sc-error" style="margin-top: 8px;">{{ $message }}</div> @enderror
            </div>

            <div class="st-field">
                <label for="lossQty">Quantité perdue</label>
                <input id="lossQty" type="number" min="1" inputmode="numeric" wire:model="lossQuantity">
                @error('lossQuantity') <div class="sc-error" style="margin-top: 8px;">{{ $message }}</div> @enderror
            </div>

            <div class="st-field">
                <label for="lossReason">Motif</label>
                <input id="lossReason" type="text" wire:model="lossReason" maxlength="255" placeholder="Ex : casier tombé, bière périmée">
                <div class="st-chips">
                    @foreach (['Casse', 'Périmé', 'Renversé', 'Offert au personnel'] as $r)
                        <button type="button" class="sc-chip {{ $lossReason === $r ? 'on' : '' }}" wire:click="$set('lossReason', @js($r))">{{ $r }}</button>
                    @endforeach
                </div>
                @error('lossReason') <div class="sc-error" style="margin-top: 8px;">{{ $message }}</div> @enderror
            </div>

            <div class="st-field" x-data="photoCapture('lossPhoto')">
                <label>Photo de la perte</label>
                <input type="file" accept="image/*" capture="environment" x-ref="file" @change="chosen($event)" hidden>
                <template x-if="!preview">
                    <button type="button" class="st-photo-btn" @click="pick()" :disabled="busy">
                        <x-icon name="phone" size="22" />
                        <span x-text="busy ? 'Préparation de la photo…' : 'Prendre une photo'"></span>
                        <small>Obligatoire : les bouteilles cassées, le produit périmé</small>
                    </button>
                </template>
                <template x-if="preview">
                    <div class="st-photo-preview">
                        <img :src="preview" alt="Photo de la perte">
                        <button type="button" class="sc-chip" @click="pick()">Reprendre la photo</button>
                    </div>
                </template>
                <div class="sc-error" x-show="error" x-text="error" x-cloak style="margin-top: 8px;"></div>
                @error('lossPhoto') <div class="sc-error" style="margin-top: 8px;">{{ $message }}</div> @enderror
            </div>

            <div class="st-total" style="background: var(--color-danger-bg);" x-show="$wire.lossProductId && costs[$wire.lossProductId]" x-cloak>
                <span>Valeur perdue au prix d'achat</span>
                <strong style="color: var(--color-danger);" x-text="((parseInt($wire.lossQuantity) || 0) * (costs[$wire.lossProductId] || 0)).toLocaleString('fr-FR').replace(/ | /g, ' ') + ' F'"></strong>
            </div>

            <button wire:click="saveLoss" class="btn btn-danger" style="margin-top: 14px; height: 46px; padding: 0 22px;">Enregistrer la perte</button>
        </div>
    @endif

    {{-- ================= HISTORIQUE ================= --}}
    @if ($tab === 'history')
        <div class="sc-bar">
            <div class="sc-seg" style="overflow-x: auto; max-width: 100%;">
                <button type="button" class="{{ $historyType === '' ? 'on' : '' }}" wire:click="$set('historyType', '')">Tout</button>
                @foreach ($movementTypes as $value => $label)
                    <button type="button" class="{{ $historyType === $value ? 'on' : '' }}" wire:click="$set('historyType', '{{ $value }}')">{{ $label }}</button>
                @endforeach
            </div>
        </div>
        <p class="small muted" style="margin: 0 0 12px;">Les ventes n'apparaissent pas ici. Vous les trouvez dans « Ventes du jour » et dans la caisse.</p>

        <div class="sc-list">
            @forelse ($movements as $m)
                @php [$ic, $fg, $bg] = $typeStyle[$m->type] ?? $typeStyle['initial']; @endphp
                <div class="st-move">
                    <div class="ic" style="background: {{ $bg }}; color: {{ $fg }};"><x-icon :name="$ic" size="19" /></div>
                    <div style="min-width: 0;">
                        <div class="t">{{ $m->product?->name ?? 'Produit supprimé' }} <span class="cat muted" style="font-weight: 500; font-size: 12.5px;">{{ $m->typeLabel() }}</span></div>
                        <div class="d">
                            {{ $m->created_at->locale('fr')->isoFormat('ddd D MMM, HH[h]mm') }} · par {{ $m->user?->first_name ?? 'inconnu' }}
                            · stock {{ $m->stock_before }} puis {{ $m->stock_after }}
                            @if ($m->supplier) · {{ $m->supplier }} @endif
                            @if ($m->unit_cost !== null) · {{ $fcfa($m->unit_cost) }} F l'unité @endif
                            @if ($m->reason) · {{ $m->reason }} @endif
                        </div>
                        @if ($m->empties_surplus)
                            <div class="d" style="color: var(--color-danger); font-weight: 700;">{{ $m->empties_surplus }} vides rendus en trop : bouteilles vendues sans bon</div>
                        @endif
                        @if ($m->photo_path)
                            <a href="{{ route('stock.photo', $m) }}" target="_blank" rel="noopener" class="st-thumb"><img src="{{ route('stock.photo', $m) }}" alt="" loading="lazy"> Voir la photo</a>
                        @elseif ($m->type === 'loss')
                            <div class="d" style="color: #9A5B0C;">Sans photo</div>
                        @endif
                    </div>
                    <div class="q" style="color: {{ $m->quantity < 0 ? 'var(--color-danger)' : 'var(--color-success)' }};">{{ $m->quantity > 0 ? '+' : '−' }}{{ abs($m->quantity) }}</div>
                </div>
            @empty
                <div style="padding: 28px 18px; text-align: center;" class="muted">
                    <x-icon name="history" size="28" />
                    <p style="margin: 8px 0 0;">Aucun mouvement pour le moment.</p>
                </div>
            @endforelse
        </div>

        @if ($movements && $movements->hasPages())
            <div style="margin-top: 12px;">{{ $movements->links() }}</div>
        @endif
    @endif
</div>
