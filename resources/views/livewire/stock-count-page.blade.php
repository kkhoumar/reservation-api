@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $days = $sheet->daysSince();
    $since = $sheet->since?->copy()->locale('fr');
    $tone = $days === null ? 'danger' : ($days <= 3 ? 'ok' : ($days <= \App\Models\StockCount::REMIND_AFTER_DAYS ? 'warn' : 'danger'));
    $ago = match (true) {
        $days === null => 'Jamais compté',
        $days === 0 => "Aujourd'hui",
        $days === 1 => 'Hier',
        default => "Il y a {$days} jours",
    };
    $kinds = \App\Models\Category::KINDS;
    $byKind = $rows->groupBy('kind');
    $totalDelivered = $rows->sum('delivered');
    $totalSold = $rows->sum('sold');
    $totalLost = $rows->sum('lost');
    $draftKey = 'me-count-' . auth()->user()->currentRestaurantId() . '-' . ($sheet->previous?->id ?? 0);
@endphp

<div class="sc">
    @include('partials.stock-count-styles')

    {{-- ================= En-tête ================= --}}
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px;">
        <div>
            <div class="eyebrow">Stock</div>
            <h1>Comptage du stock</h1>
            <p class="lead">Regardez le frigo et les casiers, tapez ce que vous voyez. L'application compare avec ses calculs depuis le dernier comptage.</p>
        </div>
        <a href="{{ route('stock.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="16" /> Stock</a>
    </div>

    <div class="sc-top">
        <div class="sc-card">
            <h3><x-icon name="calendar" size="16" /> Dernier comptage</h3>
            <div class="sc-last">
                <div>
                    @if ($since)
                        <div class="when">{{ ucfirst($since->isoFormat('dddd D MMMM')) }}</div>
                        <div class="muted small">à {{ $since->format('H\hi') }}@if ($sheet->previous?->user) · par {{ $sheet->previous->user->first_name }}@endif</div>
                    @else
                        <div class="when">Aucun comptage</div>
                        <div class="muted small">L'application part du stock qu'elle connaît aujourd'hui.</div>
                    @endif
                </div>
                <span class="sc-pill {{ $tone }}">
                    <x-icon :name="$tone === 'ok' ? 'check-circle' : ($tone === 'warn' ? 'clock' : 'alert')" size="15" /> {{ $ago }}
                </span>
            </div>
            @if ($days !== null && $days > \App\Models\StockCount::REMIND_AFTER_DAYS)
                <p class="small" style="color: var(--sc-miss); margin: 10px 0 0;">Plus de {{ \App\Models\StockCount::REMIND_AFTER_DAYS }} jours sans comptage. Plus la période est longue, plus un écart est difficile à expliquer.</p>
            @endif
        </div>

        <div class="sc-card">
            <h3><x-icon name="box" size="16" /> {{ $since ? 'Depuis le ' . $since->isoFormat('D MMMM') : 'Premier comptage' }}</h3>
            @if ($since)
                <div class="sc-moves">
                    <div class="sc-move"><strong style="color: {{ $totalDelivered ? 'var(--sc-ok)' : 'inherit' }};">{{ $totalDelivered ? '+' : '' }}{{ $fcfa($totalDelivered) }}</strong><span>{{ $totalDelivered > 1 ? 'articles livrés' : 'article livré' }}</span></div>
                    <div class="sc-move"><strong>{{ $totalSold ? '−' : '' }}{{ $fcfa($totalSold) }}</strong><span>{{ $totalSold > 1 ? 'articles vendus' : 'article vendu' }}</span></div>
                    <div class="sc-move"><strong>{{ $totalLost ? '−' : '' }}{{ $fcfa($totalLost) }}</strong><span>{{ $totalLost > 1 ? 'pertes déclarées' : 'perte déclarée' }}</span></div>
                </div>
                @if ($registers->isNotEmpty())
                    <div class="muted small" style="margin-bottom: 6px; display: flex; align-items: center; gap: 6px;"><x-icon name="users" size="14" /> Qui tenait la caisse pendant cette période</div>
                    <div class="sc-team">
                        @foreach ($registers as $r)
                            <span>{{ $r->opened_at->lt($sheet->since) ? 'Ouverte depuis le ' . $r->opened_at->locale('fr')->isoFormat('D MMM') : ucfirst($r->opened_at->locale('fr')->isoFormat('ddd D MMM')) }} · {{ $r->openedByUser?->first_name ?? 'inconnu' }}@if ($r->closedByUser && $r->closed_by !== $r->opened_by) → {{ $r->closedByUser->first_name }}@endif</span>
                        @endforeach
                    </div>
                @else
                    <div class="muted small">Aucune caisse ouverte pendant cette période.</div>
                @endif
            @else
                <p class="muted" style="font-size: 14px; margin: 0; line-height: 1.5;">
                    Dès le deuxième comptage, vous verrez pour chaque produit ce qui a été livré, vendu et perdu entre deux comptages. Vous verrez aussi qui tenait la caisse chaque jour.
                </p>
            @endif
        </div>
    </div>

    @if ($missingCost > 0)
        <div class="sc-warn">
            <x-icon name="alert" size="18" style="margin-top: 1px;" />
            <div>
                <strong>{{ $missingCost }} {{ $missingCost > 1 ? 'produits n\'ont' : 'produit n\'a' }} pas de prix d'achat.</strong>
                L'application ne peut pas calculer leur bénéfice. Elle les compte au prix de vente.
                <a href="{{ route('products.manage') }}">Compléter les prix</a>
            </div>
        </div>
    @endif

    @if (! $hasProducts)
        <div class="sc-card" style="text-align: center; padding: 32px 18px;">
            <x-icon name="box" size="32" style="color: var(--color-text-secondary);" />
            <p style="font-size: 15px; margin: 10px 0 14px;">Ajoutez d'abord vos produits pour pouvoir les compter.</p>
            <a href="{{ route('products.manage') }}" class="btn btn-primary">Ajouter des produits</a>
        </div>
    @elseif ($readOnly)
        <div class="sc-warn"><x-icon name="lock" size="18" /> <div>{{ \App\Support\ReadOnlyGuard::message() }}</div></div>
    @else
        {{-- ================= Comptage (calculs dans le téléphone) ================= --}}
        <div wire:ignore x-data="stockCount(@js($rows->keyBy('id')), @js($draftKey))">
            <div class="sc-bar">
                <div class="sc-seg" role="tablist">
                    <button type="button" :class="{ on: kind === 'all' }" @click="kind = 'all'">Tout</button>
                    @foreach ($kinds as $k => $label)
                        @if ($byKind->has($k))
                            <button type="button" :class="{ on: kind === '{{ $k }}' }" @click="kind = '{{ $k }}'">{{ $label }}</button>
                        @endif
                    @endforeach
                    <button type="button" :class="{ on: kind === 'gaps' }" @click="kind = 'gaps'">Écarts <span x-show="gapCount() > 0" x-text="'(' + gapCount() + ')'"></span></button>
                </div>
                <div class="sc-progress">
                    <div class="small" style="display: flex; justify-content: space-between;">
                        <span><strong x-text="countedCount()"></strong> / {{ $rows->count() }} produits comptés</span>
                        <button type="button" x-show="countedCount() > 0" @click="clearAll()" style="border: 0; background: none; color: var(--color-text-secondary); text-decoration: underline; cursor: pointer; font: inherit; font-size: 12px; padding: 0;">Tout effacer</button>
                    </div>
                    <div class="track"><div class="fill" :style="'width:' + (countedCount() / {{ max(1, $rows->count()) }} * 100) + '%'"></div></div>
                </div>
            </div>

            <div x-show="restored" x-transition class="sc-warn" style="background: var(--color-mint); color: var(--color-primary);">
                <x-icon name="clock" size="18" /> <div>Vos chiffres non validés sont revenus. Continuez là où vous en étiez.</div>
            </div>

            @foreach ($kinds as $k => $label)
                @continue(! $byKind->has($k))
                <section class="sc-section" x-show="kind === 'all' || kind === '{{ $k }}' || (kind === 'gaps' && kindGapCount('{{ $k }}') > 0)">
                    <div class="sc-section-head">
                        <h2><span class="sc-h-ico {{ $k }}"><x-icon :name="$k === 'drink' ? 'bottle' : 'utensils'" size="17" /></span>{{ $label }}</h2>
                        <span class="muted small"><span x-text="kindCounted('{{ $k }}')"></span> / {{ $byKind[$k]->count() }} comptés</span>
                    </div>
                    <div class="sc-list">
                        @foreach ($byKind[$k] as $r)
                            @php $start = $r['expected'] - $r['delivered'] + $r['sold'] + $r['lost']; @endphp
                            <div class="sc-row" :class="state({{ $r['id'] }})" x-show="kind !== 'gaps' || (gap({{ $r['id'] }}) ?? 0) !== 0" wire:key="row-{{ $r['id'] }}">
                                <div class="sc-info with-ava">
                                    <x-stock-ava :kind="$r['kind']" />
                                    <div class="sc-txt">
                                    <div class="sc-name">{{ $r['name'] }}@if ($r['category'] && mb_strtolower($r['category']) !== mb_strtolower($label))<span class="cat">{{ $r['category'] }}</span>@endif @if ($r['cost'] === null)<span class="sc-tag">prix d'achat manquant</span>@endif</div>
                                    <div class="sc-calc">
                                        @if ($since)
                                            Le {{ $since->isoFormat('D MMM') }} : <span class="out">{{ $start }}</span>
                                            @if ($r['delivered']) · <span class="in">+{{ $r['delivered'] }} {{ $r['delivered'] > 1 ? 'livrés' : 'livré' }}</span>@endif
                                            @if ($r['sold']) · <span class="out">−{{ $r['sold'] }} {{ $r['sold'] > 1 ? 'vendus' : 'vendu' }}</span>@endif
                                            @if ($r['lost']) · <span class="out">−{{ $r['lost'] }} {{ $r['lost'] > 1 ? 'perdus' : 'perdu' }}</span>@endif
                                            @if (! $r['delivered'] && ! $r['sold'] && ! $r['lost']) · aucun mouvement @endif
                                        @else
                                            {{ $fcfa($r['price']) }} F la vente @if ($r['cost'] !== null) · {{ $fcfa($r['cost']) }} F l'achat @endif
                                        @endif
                                    </div>
                                    </div>
                                </div>
                                <div class="sc-expected"><small>Doit rester</small><strong>{{ $r['expected'] }}</strong></div>
                                <div class="sc-input">
                                    <button type="button" class="sc-step" aria-label="Un de moins" @click="step({{ $r['id'] }}, -1)"><x-icon name="minus" size="18" stroke="2.4" /></button>
                                    <input type="text" inputmode="numeric" autocomplete="off" enterkeyhint="next" placeholder="—" aria-label="Compté : {{ $r['name'] }}"
                                           :value="counts[{{ $r['id'] }}] ?? ''" @input="type({{ $r['id'] }}, $event)" @focus="$event.target.select()">
                                    <button type="button" class="sc-step" aria-label="Un de plus" @click="step({{ $r['id'] }}, 1)"><x-icon name="plus" size="18" stroke="2.4" /></button>
                                </div>
                                <button type="button" class="sc-exact" :class="{ on: gap({{ $r['id'] }}) === 0 }" @click="exact({{ $r['id'] }})" title="Le compte est bon"><x-icon name="check" size="15" stroke="2.6" /> Juste</button>
                                @if ($r['returnable'] && $r['crate'])
                                    {{-- Pleines : compter en casiers, l'application fait le calcul --}}
                                    <div class="sc-crates">
                                        <span class="lbl">Ou en casiers de {{ $r['crate'] }}&nbsp;:</span>
                                        <span class="sc-cr">
                                            <input type="text" inputmode="numeric" autocomplete="off" placeholder="0" aria-label="Casiers pleins : {{ $r['name'] }}"
                                                   :value="crPart('f', {{ $r['id'] }}, 'c')" @input="typeCrate('f', {{ $r['id'] }}, 'c', $event)" @focus="$event.target.select()">
                                            <small>casiers</small>
                                        </span>
                                        <span class="plus">+</span>
                                        <span class="sc-cr">
                                            <input type="text" inputmode="numeric" autocomplete="off" placeholder="0" aria-label="Bouteilles pleines en plus : {{ $r['name'] }}"
                                                   :value="crPart('f', {{ $r['id'] }}, 'l')" @input="typeCrate('f', {{ $r['id'] }}, 'l', $event)" @focus="$event.target.select()">
                                            <small>bouteilles</small>
                                        </span>
                                    </div>
                                @endif
                                @if ($r['returnable'])
                                    <div class="sc-empties">
                                        <div class="sc-em-head">
                                            <span class="lbl"><x-icon name="bottle" size="16" /> Bouteilles vides</span>
                                            <span class="exp">
                                                @if ($r['emptiesExpected'] !== null)
                                                    @php
                                                        $ec = $r['crate'] ? intdiv($r['emptiesExpected'], $r['crate']) : 0;
                                                        $el = $r['crate'] ? $r['emptiesExpected'] % $r['crate'] : 0;
                                                    @endphp
                                                    L'application attend <b>{{ $r['emptiesExpected'] }}</b> vides{{ $ec > 0 ? ' (' . $ec . ' ' . ($ec > 1 ? 'casiers' : 'casier') . ($el ? ' + ' . $el : '') . ')' : '' }}
                                                @else
                                                    Premier comptage des vides : le contrôle commencera la prochaine fois.
                                                @endif
                                            </span>
                                        </div>
                                        <div class="sc-em-fields">
                                            @if ($r['crate'])
                                                <span class="sc-cr">
                                                    <input type="text" inputmode="numeric" autocomplete="off" placeholder="0" aria-label="Casiers de vides : {{ $r['name'] }}"
                                                           :value="crPart('e', {{ $r['id'] }}, 'c')" @input="typeCrate('e', {{ $r['id'] }}, 'c', $event)" @focus="$event.target.select()">
                                                    <small>casiers</small>
                                                </span>
                                                <span class="plus">+</span>
                                                <span class="sc-cr">
                                                    <input type="text" inputmode="numeric" autocomplete="off" placeholder="0" aria-label="Bouteilles vides en plus : {{ $r['name'] }}"
                                                           :value="crPart('e', {{ $r['id'] }}, 'l')" @input="typeCrate('e', {{ $r['id'] }}, 'l', $event)" @focus="$event.target.select()">
                                                    <small>bouteilles</small>
                                                </span>
                                                <span class="eq" x-show="emptyValue({{ $r['id'] }}) !== null" x-cloak>= <b x-text="emptyValue({{ $r['id'] }})"></b></span>
                                            @else
                                                <span class="sc-cr">
                                                    <input type="text" inputmode="numeric" autocomplete="off" placeholder="—" aria-label="Bouteilles vides trouvées : {{ $r['name'] }}"
                                                           :value="empties[{{ $r['id'] }}] ?? ''" @input="typeEmpty({{ $r['id'] }}, $event)" @focus="$event.target.select()">
                                                    <small>trouvées</small>
                                                </span>
                                            @endif
                                            <span class="br" aria-hidden="true"></span>
                                            <span class="sc-cr broken">
                                                <input type="text" inputmode="numeric" autocomplete="off" placeholder="0" aria-label="Bouteilles vides cassées : {{ $r['name'] }}"
                                                       :value="broken[{{ $r['id'] }}] ?? ''" @input="typeBroken({{ $r['id'] }}, $event)" @focus="$event.target.select()">
                                                <small>cassées</small>
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                <div class="sc-result" x-show="(gap({{ $r['id'] }}) ?? 0) !== 0" x-cloak>
                                    <span class="sc-badge" :class="state({{ $r['id'] }})" x-html="badge({{ $r['id'] }})"></span>
                                    <span class="sc-why" x-show="why({{ $r['id'] }})" x-html="why({{ $r['id'] }})"></span>
                                    <div class="sc-reasons" x-show="gap({{ $r['id'] }}) !== 0">
                                        <span>Pourquoi ?</span>
                                        @foreach (\App\Models\StockCountLine::REASONS as $rk => $rl)
                                            <button type="button" class="sc-chip" :class="{ on: reasons[{{ $r['id'] }}] === '{{ $rk }}' }" @click="reason({{ $r['id'] }}, '{{ $rk }}')">{{ $rl }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="sc-list" style="margin-top: 8px;" x-show="kindCounted('{{ $k }}') > 0" x-cloak>
                        <div class="sc-sub" style="border-top: 0;">
                            <span>Total {{ mb_strtolower($label) }} comptés : <strong x-text="money(totals('{{ $k }}').sale)"></strong> <span class="muted">au prix de vente</span></span>
                            <span>Écart : <strong :style="'color:' + tone(totals('{{ $k }}').gapSale)" x-text="signed(totals('{{ $k }}').gapSale)"></strong></span>
                        </div>
                    </div>
                </section>
            @endforeach

            {{-- ================= Résumé et validation ================= --}}
            <section id="sc-resume" class="sc-section" style="scroll-margin-top: 16px;">
                <div class="sc-section-head"><h2>Résumé du comptage</h2></div>
                <div class="sc-summary">
                    <template x-for="g in groups()" :key="g.key">
                        <div class="sc-sum" :class="{ total: g.key === 'total' }">
                            <h3><span style="font-size: 16px; font-weight: 800;" x-text="g.label"></span><span class="muted" x-text="g.counted + ' / ' + g.size + ' comptés'"></span></h3>
                            <div class="line"><span class="muted">Valeur au prix d'achat</span><strong x-text="money(g.cost)"></strong></div>
                            <div class="line"><span class="muted">Valeur au prix de vente</span><strong x-text="money(g.sale)"></strong></div>
                            <div class="line"><span class="muted">Bénéfice possible</span><strong x-text="money(g.sale - g.cost)"></strong></div>
                            <div class="gap" :class="g.counted === 0 ? 'none' : (g.gapSale < 0 ? 'miss' : (g.gapSale > 0 ? 'over' : 'ok'))">
                                <span x-text="g.counted === 0 ? 'Rien de compté' : (g.gapSale < 0 ? 'Manque' : (g.gapSale > 0 ? 'En trop' : 'Tout est juste'))"></span>
                                <span x-show="g.counted > 0 && g.gapSale !== 0" x-text="signed(g.gapSale)"></span>
                            </div>
                            <div class="small muted" style="margin-top: 6px;" x-show="g.counted > 0 && g.gapSale !== 0" x-text="'Soit ' + signed(g.gapCost) + ' au prix d\'achat'"></div>
                            <div class="small" style="margin-top: 6px; font-weight: 700;" :style="g.key === 'total' ? 'color:#FFD3D3' : 'color: var(--color-danger)'" x-show="g.unbilled > 0" x-text="'Dont ' + money(g.unbilled) + ' de boissons peut-être vendues sans bon'"></div>
                        </div>
                    </template>
                </div>

                <div class="sc-card">
                    <div class="sc-validate">
                        <div>
                            <label for="sc-note" style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">Note <span class="muted" style="font-weight: 500;">(facultatif)</span></label>
                            <input id="sc-note" type="text" maxlength="255" x-model="note" placeholder="Ex : un casier de Flag rendu au dépôt">
                        </div>
                        <button type="button" class="btn btn-primary" style="height: 46px; padding: 0 22px;" :disabled="sending || countedCount() === 0" @click="submit()">
                            <x-icon name="check-circle" size="18" />
                            <span x-text="sending ? 'Validation…' : 'Valider le comptage'"></span>
                        </button>
                    </div>
                    <p class="small muted" style="margin: 10px 0 0; line-height: 1.5;">
                        Un produit laissé vide garde son stock. Après validation, l'application remplace son stock par vos chiffres.
                    </p>
                    <div class="sc-error" x-show="error" x-text="error" x-cloak></div>
                </div>
            </section>

            {{-- Barre en bas de l'écran : où j'en suis --}}
            <div class="sc-sticky" x-show="countedCount() > 0" x-transition.opacity x-cloak>
                <div class="sc-sticky-in">
                    <div class="info">
                        <strong x-text="countedCount() + ' / {{ $rows->count() }}'"></strong> <span class="muted">comptés</span><br>
                        <span class="muted">Écart :</span> <strong :style="'color:' + tone(totals('all').gapSale)" x-text="signed(totals('all').gapSale)"></strong>
                    </div>
                    <button type="button" class="btn btn-primary" @click="document.getElementById('sc-resume').scrollIntoView({ behavior: 'smooth' })">Voir le résumé</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= Comptages précédents ================= --}}
    @if ($history->isNotEmpty())
        <section class="sc-section" style="margin-top: 28px;">
            <div class="sc-section-head"><h2>Comptages précédents</h2></div>
            <div class="sc-list sc-hist">
                @foreach ($history as $c)
                    @php $d = $c->created_at->copy()->locale('fr'); @endphp
                    <a href="{{ route('stock.count.show', $c) }}">
                        <div style="min-width: 0;">
                            <div class="d">{{ ucfirst($d->isoFormat('dddd D MMMM YYYY')) }}</div>
                            <div class="small muted">{{ $d->format('H\hi') }} · {{ $c->products_counted }} {{ $c->products_counted > 1 ? 'produits' : 'produit' }} · par {{ $c->user?->first_name ?? '—' }}</div>
                        </div>
                        @php $g = $c->gap_sale_value ?: $c->value_gap; @endphp
                        <div class="r" style="color: {{ $g < 0 ? 'var(--sc-miss)' : ($g > 0 ? 'var(--sc-over)' : 'var(--sc-ok)') }};">
                            {{ $g === 0 ? 'Juste' : ($g > 0 ? '+' : '−') . $fcfa(abs($g)) . ' F' }}
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <script>
        window.stockCount = (rows, draftKey) => ({
            rows,
            counts: {},
            empties: {},
            broken: {},
            crates: {},
            reasons: {},
            note: '',
            kind: 'all',
            sending: false,
            error: '',
            restored: false,

            init() {
                try {
                    const draft = JSON.parse(localStorage.getItem(draftKey) || 'null');
                    if (draft && draft.counts && Object.keys(draft.counts).length) {
                        for (const id in draft.counts) if (this.rows[id]) this.counts[id] = draft.counts[id];
                        this.reasons = draft.reasons || {};
                        this.empties = draft.empties || {};
                        this.broken = draft.broken || {};
                        this.note = draft.note || '';
                        this.restored = true;
                        setTimeout(() => this.restored = false, 6000);
                    }
                } catch (e) {}
                this.$watch('counts', () => this.saveDraft());
                this.$watch('reasons', () => this.saveDraft());
                this.$watch('note', () => this.saveDraft());
            },

            saveDraft() {
                try { localStorage.setItem(draftKey, JSON.stringify({ counts: this.counts, reasons: this.reasons, note: this.note, empties: this.empties, broken: this.broken })); } catch (e) {}
            },

            value(id) {
                const v = this.counts[id];
                return v === undefined || v === '' ? null : parseInt(v, 10);
            },
            gap(id) {
                const v = this.value(id);
                return v === null ? null : v - this.rows[id].expected;
            },
            state(id) {
                const g = this.gap(id);
                return g === null ? '' : (g === 0 ? 'ok' : (g < 0 ? 'miss' : 'over'));
            },
            typeEmpty(id, e) {
                const d = e.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 6);
                e.target.value = d;
                if (d === '') { delete this.empties[id]; this.empties = { ...this.empties }; } else { this.empties[id] = d; }
                this.saveDraft();
            },
            // Casiers + bouteilles : 'f' = pleines (counts), 'e' = vides (empties)
            crPart(which, id, part) {
                const key = which + id, size = this.rows[id].crate;
                if (this.crates[key]) return this.crates[key][part];
                const total = which === 'f' ? this.value(id) : this.emptyValue(id);
                if (total === null) return '';
                return String(part === 'c' ? Math.floor(total / size) : total % size);
            },
            typeCrate(which, id, part, e) {
                const d = e.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 4);
                e.target.value = d;
                const key = which + id, size = this.rows[id].crate;
                const cur = this.crates[key] ? { ...this.crates[key] } : { c: this.crPart(which, id, 'c'), l: this.crPart(which, id, 'l') };
                cur[part] = d;
                this.crates = { ...this.crates, [key]: cur };
                const target = which === 'f' ? 'counts' : 'empties';
                const next = { ...this[target] };
                if (cur.c === '' && cur.l === '') delete next[id];
                else next[id] = String(parseInt(cur.c || '0', 10) * size + parseInt(cur.l || '0', 10));
                this[target] = next;
                this.error = '';
                this.saveDraft();
            },
            typeBroken(id, e) {
                const d = e.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 4);
                e.target.value = d;
                const next = { ...this.broken };
                if (d === '' || d === '0') delete next[id]; else next[id] = d;
                this.broken = next;
                this.saveDraft();
            },
            brokenValue(id) {
                const v = this.broken[id];
                return v === undefined || v === '' ? 0 : parseInt(v, 10);
            },
            emptyValue(id) {
                const v = this.empties[id];
                return v === undefined || v === '' ? null : parseInt(v, 10);
            },
            // Bouteilles qui manquent : vendues sans bon (vides en trop) ou sorties du maquis
            split(id) {
                const r = this.rows[id], g = this.gap(id), e = this.emptyValue(id);
                if (!r.returnable || r.emptiesExpected === null || e === null || g === null || g >= 0) return null;
                const missing = -g;
                // Un vide cassé est une bière bue ici : il compte comme un vide trouvé
                const unbilled = Math.max(0, Math.min(missing, e + this.brokenValue(id) - r.emptiesExpected));
                return { unbilled, out: missing - unbilled };
            },
            why(id) {
                const s = this.split(id), r = this.rows[id];
                if (!s) return '';
                const parts = [];
                if (s.unbilled) parts.push('<b>' + s.unbilled + ' peut-être vendue' + (s.unbilled > 1 ? 's' : '') + ' sans bon</b> : vides en trop, ' + this.signed(-s.unbilled * r.price) + ' dans la caisse');
                if (s.out) parts.push('<b>' + s.out + ' disparue' + (s.out > 1 ? 's' : '') + '</b> : ni bon ni bouteille vide');
                return parts.join(' · ') + '. Vérifiez avec votre équipe.';
            },
            type(id, e) {
                const d = e.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 6);
                e.target.value = d;
                if (d === '') { delete this.counts[id]; this.counts = { ...this.counts }; } else { this.counts[id] = d; }
                this.dropCrate(id);
                this.error = '';
            },
            step(id, delta) {
                // Premier appui : on part de ce qui doit rester (34 → − → 33)
                const base = this.value(id) ?? Math.max(0, this.rows[id].expected);
                this.counts[id] = String(Math.max(0, base + delta));
                this.dropCrate(id);
                this.error = '';
            },
            exact(id) {
                this.counts[id] = String(Math.max(0, this.rows[id].expected));
                this.dropCrate(id);
                delete this.reasons[id];
                this.error = '';
            },
            dropCrate(id) {
                if (this.crates['f' + id]) { const c = { ...this.crates }; delete c['f' + id]; this.crates = c; }
            },
            reason(id, key) {
                this.reasons[id] = this.reasons[id] === key ? null : key;
            },
            clearAll() {
                if (!confirm('Effacer tout ce que vous avez compté ?')) return;
                this.counts = {}; this.reasons = {}; this.note = ''; this.empties = {}; this.broken = {}; this.crates = {};
            },

            unitCost(r) { return r.cost ?? r.price; },
            ids(kind) { return Object.keys(this.rows).filter(id => kind === 'all' || this.rows[id].kind === kind); },
            countedCount() { return Object.keys(this.counts).filter(id => this.value(id) !== null).length; },
            kindCounted(kind) { return this.ids(kind).filter(id => this.value(id) !== null).length; },
            gapCount() { return Object.keys(this.rows).filter(id => (this.gap(id) ?? 0) !== 0).length; },
            kindGapCount(kind) { return this.ids(kind).filter(id => (this.gap(id) ?? 0) !== 0).length; },

            totals(kind) {
                const t = { size: 0, counted: 0, cost: 0, sale: 0, gapSale: 0, gapCost: 0, unbilled: 0 };
                for (const id of this.ids(kind)) {
                    const r = this.rows[id], v = this.value(id);
                    t.size++;
                    if (v === null) continue;
                    t.counted++;
                    t.cost += v * this.unitCost(r);
                    t.sale += v * r.price;
                    t.gapSale += (v - r.expected) * r.price;
                    t.gapCost += (v - r.expected) * this.unitCost(r);
                    const s = this.split(id);
                    if (s) t.unbilled += s.unbilled * r.price;
                }
                return t;
            },
            groups() {
                const labels = @js($kinds);
                const out = [];
                for (const k in labels) {
                    if (this.ids(k).length) out.push({ key: k, label: labels[k], ...this.totals(k) });
                }
                out.push({ key: 'total', label: 'Total', ...this.totals('all') });
                return out;
            },

            money(n) { return (n < 0 ? '−' : '') + Math.abs(n).toLocaleString('fr-FR').replace(/ | /g, ' ') + ' F'; },
            signed(n) { return n === 0 ? '0 F' : (n > 0 ? '+' : '−') + Math.abs(n).toLocaleString('fr-FR').replace(/ | /g, ' ') + ' F'; },
            tone(n) { return n < 0 ? 'var(--color-danger)' : (n > 0 ? '#2463B5' : 'var(--color-success)'); },
            badge(id) {
                const g = this.gap(id), r = this.rows[id];
                if (g === null) return '';
                if (g === 0) return 'Juste';
                const n = Math.abs(g);
                return (g < 0 ? 'Il manque ' + n : n + ' en trop') + ' · ' + this.signed(g * r.price);
            },

            async submit() {
                const n = this.countedCount();
                const gap = this.totals('all').gapSale;
                const msg = 'Valider le comptage de ' + n + ' produit' + (n > 1 ? 's' : '') + ' ?\n'
                    + (gap === 0 ? 'Tout est juste.' : 'Écart : ' + this.signed(gap) + '.')
                    + '\nLe stock de l\'application sera remplacé par vos chiffres.';
                if (!confirm(msg)) return;

                this.sending = true;
                this.error = '';
                const counts = {}, reasons = {}, empties = {}, broken = {};
                for (const id in this.empties) if (this.emptyValue(id) !== null && this.value(id) !== null) empties[id] = String(this.emptyValue(id));
                for (const id in this.counts) if (this.value(id) !== null) counts[id] = String(this.value(id));
                for (const id in this.broken) if (this.brokenValue(id) > 0 && empties[id] !== undefined) broken[id] = String(this.brokenValue(id));
                for (const id in this.reasons) if (this.reasons[id] && counts[id] !== undefined) reasons[id] = this.reasons[id];

                try {
                    const error = await this.$wire.save(counts, reasons, this.note, empties, broken);
                    if (error) { this.error = error; this.sending = false; return; }
                    try { localStorage.removeItem(draftKey); } catch (e) {}
                } catch (e) {
                    this.error = 'La connexion a coupé. Vos chiffres sont gardés. Réessayez.';
                    this.sending = false;
                }
            },
        });
    </script>
</div>
