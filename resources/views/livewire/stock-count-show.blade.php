@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $signed = fn ($n) => $n === 0 ? '0 F' : ($n > 0 ? '+' : '−') . $fcfa(abs($n)) . ' F';
    $date = $count->created_at->copy()->locale('fr');
    $prev = $count->previous_count_at?->copy()->locale('fr');
    $gaps = $count->lines->where('gap', '!=', 0)->sortBy(fn ($l) => $l->gap * $l->unit_price)->values();
    $right = $count->lines->where('gap', 0)->sortBy('product_name')->values();
    $kinds = \App\Models\Category::KINDS;
    $tone = fn ($n) => $n < 0 ? 'miss' : ($n > 0 ? 'over' : 'ok');
@endphp

<div class="sc">
    @include('partials.stock-count-styles')
    <style>
        .scs-hero { display: flex; gap: 16px; align-items: center; border-radius: 18px; padding: 20px 22px; margin: 18px 0 14px; color: #fff; box-shadow: 0 12px 30px -12px rgba(16,35,29,.35); }
        .scs-hero.ok { background: linear-gradient(135deg, #0B4D3C, #1E8E5A); }
        .scs-hero.miss { background: linear-gradient(135deg, #8E1F1F, #D93B3B); }
        .scs-hero.over { background: linear-gradient(135deg, #173F75, #2463B5); }
        .scs-hero .ic { width: 54px; height: 54px; border-radius: 16px; background: rgba(255,255,255,.16); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .scs-hero .lbl { font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; opacity: .8; }
        .scs-hero .big { font-size: 28px; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; margin: 2px 0 8px; }
        .scs-hero .meta { display: flex; flex-wrap: wrap; gap: 6px; }
        .scs-hero .meta span { font-size: 12.5px; font-weight: 600; background: rgba(255,255,255,.16); padding: 4px 10px; border-radius: 999px; }
        @media (max-width: 520px) { .scs-hero { padding: 18px; gap: 12px; align-items: flex-start; } .scs-hero .ic { width: 44px; height: 44px; } .scs-hero .big { font-size: 23px; } }
        .scs-line { display: grid; grid-template-columns: minmax(0, 1fr) repeat(3, 84px) 120px; gap: 8px 14px; align-items: center; padding: 13px 18px; border-top: 1px solid var(--color-border); }
        .scs-line:first-child { border-top: 0; }
        .scs-line.head { font-size: 11.5px; font-weight: 700; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .03em; padding-top: 10px; padding-bottom: 10px; background: var(--color-bg); }
        .scs-num { text-align: center; font-weight: 700; font-size: 15px; }
        .scs-num small { display: none; font-size: 11px; font-weight: 600; color: var(--color-text-secondary); text-transform: uppercase; }
        .scs-val { text-align: right; font-weight: 800; white-space: nowrap; }
        .scs-wa { display: flex; flex-wrap: wrap; gap: 10px; }
        .scs-wa .btn { display: inline-flex; align-items: center; gap: 8px; }
        .scs-wa .btn-wa { background: #1FA855; color: #fff; border: 0; }
        details.scs-right summary { cursor: pointer; list-style: none; padding: 14px 18px; font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
        details.scs-right summary::-webkit-details-marker { display: none; }
        details.scs-right summary .chev { transition: transform .2s; }
        details.scs-right[open] summary .chev { transform: rotate(180deg); }
        @media (max-width: 760px) {
            .scs-line { grid-template-columns: repeat(3, minmax(0, 1fr)); padding: 14px; }
            .scs-line.head { display: none; }
            .scs-line .sc-info { grid-column: 1 / -1; }
            .scs-num { background: var(--color-bg); border-radius: 10px; padding: 6px 4px; }
            .scs-num small { display: block; }
            .scs-val { grid-column: 1 / -1; text-align: left; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
            .scs-wa .btn { flex: 1 1 100%; justify-content: center; }
        }
    </style>

    @if (session('success'))
        <div class="sc-warn" style="background: var(--color-success-bg); color: var(--color-success);">
            <x-icon name="check-circle" size="18" /> <div><strong>{{ session('success') }}</strong></div>
        </div>
    @endif

    {{-- ================= En-tête ================= --}}
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px;">
        <div>
            <div class="eyebrow">Comptage du stock</div>
            <h1>{{ ucfirst($date->isoFormat('dddd D MMMM YYYY')) }}</h1>
            <p class="lead">
                À {{ $date->format('H\hi') }}@if ($count->user), par {{ $count->user->first_name }}@endif.
                @if ($prev)
                    Période : depuis le comptage du {{ $prev->isoFormat('dddd D MMMM') }} ({{ $report->periodLabel() }}).
                @else
                    Premier comptage du maquis.
                @endif
            </p>
        </div>
        <a href="{{ route('stock.count') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="16" /> Comptage</a>
    </div>

    {{-- ================= Résultat en un coup d'œil ================= --}}
    @php
        $total = $summary['total'];
        $heroTone = $tone($total['gap_sale']);
        $heroText = $total['gap_sale'] === 0 && $gaps->isEmpty()
            ? 'Tout est juste'
            : ($total['gap_sale'] < 0 ? 'Il manque ' . $fcfa(abs($total['gap_sale'])) . ' F' : ($total['gap_sale'] > 0 ? $fcfa($total['gap_sale']) . ' F en trop' : 'Les écarts s\'annulent'));
    @endphp
    <div class="scs-hero {{ $heroTone }}">
        <div class="ic"><x-icon :name="$heroTone === 'ok' ? 'check-circle' : 'alert'" size="28" /></div>
        <div style="min-width: 0;">
            <div class="lbl">Résultat au prix de vente</div>
            <div class="big">{{ $heroText }}</div>
            <div class="meta">
                <span>{{ $total['products'] }} {{ $total['products'] > 1 ? 'produits comptés' : 'produit compté' }}</span>
                <span>{{ $gaps->count() }} {{ $gaps->count() > 1 ? 'écarts' : 'écart' }}</span>
                @if ($prev)<span>Période de {{ $report->periodLabel() }}</span>@endif
                @if ($total['unbilled'] > 0)<span>{{ $fcfa($total['unbilled']) }} F vendus sans bon</span>@endif
            </div>
        </div>
    </div>
    {{-- ================= Envoi WhatsApp ================= --}}
    <div class="sc-card" style="margin: 0 0 18px;">
        <h3><x-icon name="message" size="16" /> Envoyer le résumé</h3>
        <div class="scs-wa">
            @if ($ownerUrl)
                <a href="{{ $ownerUrl }}" target="_blank" rel="noopener" class="btn btn-wa"><x-icon name="message" size="17" /> Envoyer au patron sur WhatsApp</a>
            @endif
            <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="btn btn-ghost"><x-icon name="share" size="17" /> Partager à quelqu'un d'autre</a>
        </div>
    </div>

    {{-- ================= Totaux ================= --}}
    <div class="sc-section">
        <div class="sc-section-head"><h2>Résumé</h2></div>
        <div class="sc-summary">
            @foreach ($summary as $key => $g)
                <div class="sc-sum {{ $key === 'total' ? 'total' : '' }}">
                    <h3><span style="font-size: 16px; font-weight: 800;">{{ $g['label'] }}</span><span class="muted">{{ $g['products'] }} {{ $g['products'] > 1 ? 'comptés' : 'compté' }}</span></h3>
                    <div class="line"><span class="muted">Valeur au prix d'achat</span><strong>{{ $fcfa($g['cost']) }} F</strong></div>
                    <div class="line"><span class="muted">Valeur au prix de vente</span><strong>{{ $fcfa($g['sale']) }} F</strong></div>
                    <div class="line"><span class="muted">Bénéfice possible</span><strong>{{ $fcfa($g['margin']) }} F</strong></div>
                    <div class="gap {{ $tone($g['gap_sale']) }}">
                        <span>{{ $g['gap_sale'] < 0 ? 'Manque' : ($g['gap_sale'] > 0 ? 'En trop' : 'Tout est juste') }}</span>
                        @if ($g['gap_sale'] !== 0)<span>{{ $signed($g['gap_sale']) }}</span>@endif
                    </div>
                    @if ($g['gap_sale'] !== 0)
                        <div class="small muted" style="margin-top: 6px;">Soit {{ $signed($g['gap_cost']) }} au prix d'achat</div>
                    @endif
                    @if ($g['unbilled'] > 0)
                        <div class="small" style="margin-top: 6px; font-weight: 700; color: {{ $key === 'total' ? '#FFD3D3' : 'var(--color-danger)' }};">Dont {{ $fcfa($g['unbilled']) }} F de boissons vendues sans bon</div>
                    @endif
                    @if ($g['missing_cost'] > 0 && $key !== 'total')
                        <div class="small muted" style="margin-top: 6px;">{{ $g['missing_cost'] }} sans prix d'achat, comptés au prix de vente</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= Écarts ================= --}}
    <section class="sc-section">
        <div class="sc-section-head">
            <h2>{{ $gaps->isEmpty() ? 'Aucun écart' : 'Les écarts' }}</h2>
            @if ($gaps->isNotEmpty())<span class="muted small">{{ $gaps->count() }} {{ $gaps->count() > 1 ? 'produits' : 'produit' }}</span>@endif
        </div>

        @if ($gaps->isEmpty())
            <div class="sc-card" style="display: flex; align-items: center; gap: 12px; color: var(--color-success);">
                <x-icon name="check-circle" size="26" />
                <div><strong>Chaque produit compté correspond au stock de l'application.</strong></div>
            </div>
        @else
            <div class="sc-list">
                <div class="scs-line head"><span>Produit</span><span style="text-align:center">Attendu</span><span style="text-align:center">Compté</span><span style="text-align:center">Écart</span><span style="text-align:right">Valeur</span></div>
                @foreach ($gaps as $l)
                    <div class="scs-line">
                        <div class="sc-info with-ava">
                            <x-stock-ava :kind="$l->kind" />
                            <div class="sc-txt">
                            <div class="sc-name">{{ $l->product_name }}</div>
                            @if ($prev)
                                <div class="sc-calc">
                                    Le {{ $prev->isoFormat('D MMM') }} : <span class="out">{{ $l->startQuantity() }}</span>
                                    @if ($l->delivered) · <span class="in">+{{ $l->delivered }} {{ $l->delivered > 1 ? 'livrés' : 'livré' }}</span>@endif
                                    @if ($l->sold) · <span class="out">−{{ $l->sold }} {{ $l->sold > 1 ? 'vendus' : 'vendu' }}</span>@endif
                                    @if ($l->lost) · <span class="out">−{{ $l->lost }} {{ $l->lost > 1 ? 'perdus' : 'perdu' }}</span>@endif
                                </div>
                            @endif
                            @if ($l->empties_counted !== null)
                                <div class="sc-calc">
                                    Vides : <span class="out">{{ $l->empties_counted }}</span> comptés{{ $l->empties_expected !== null ? ', ' . $l->empties_expected . ' attendus' : ' (premier comptage des vides)' }}
                                </div>
                            @endif
                            @if ($l->unbilled || $l->taken_out)
                                <div class="sc-why" style="margin-top: 3px;">
                                    @if ($l->unbilled)<b>{{ $l->unbilled }} {{ $l->unbilled > 1 ? 'vendues' : 'vendue' }} sans bon</b> ({{ $signed(-$l->unbilled * $l->unit_price) }})@endif
                                    @if ($l->unbilled && $l->taken_out) · @endif
                                    @if ($l->taken_out)<b>{{ $l->taken_out }} {{ $l->taken_out > 1 ? 'sorties' : 'sortie' }} du maquis</b>@endif
                                </div>
                            @endif
                            </div>
                        </div>
                        <div class="scs-num"><small>Attendu</small>{{ $l->expected }}</div>
                        <div class="scs-num"><small>Compté</small>{{ $l->counted }}</div>
                        <div class="scs-num" style="color: {{ $l->gap < 0 ? 'var(--sc-miss)' : 'var(--sc-over)' }};"><small>Écart</small>{{ $l->gap > 0 ? '+' : '−' }}{{ abs($l->gap) }}</div>
                        <div class="scs-val">
                            <span class="sc-badge {{ $tone($l->gap) }}">{{ $signed($l->gap * $l->unit_price) }}</span>
                            <span class="small muted" style="display: block; margin-top: 4px; font-weight: 600;">{{ $l->reasonLabel() ?? 'Raison non indiquée' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ================= Produits justes ================= --}}
    @if ($right->isNotEmpty())
        <section class="sc-section">
            <details class="sc-list scs-right">
                <summary>
                    <span style="display: flex; align-items: center; gap: 8px; color: var(--color-success);"><x-icon name="check-circle" size="18" /> {{ $right->count() }} {{ $right->count() > 1 ? 'produits justes' : 'produit juste' }}</span>
                    <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                @foreach ($right as $l)
                    <div class="sc-hist" style="display: flex; justify-content: space-between; gap: 12px; padding: 10px 18px; border-top: 1px solid var(--color-border); font-size: 14px;">
                        <span>{{ $l->product_name }} <span class="muted small">{{ $kinds[$l->kind] ?? '' }}</span></span>
                        <strong style="white-space: nowrap;">{{ $l->counted }} <span class="muted small" style="font-weight: 500;">· {{ $fcfa($l->counted * $l->unit_price) }} F</span></strong>
                    </div>
                @endforeach
            </details>
        </section>
    @endif

    @if ($count->note)
        <div class="sc-card" style="margin-bottom: 18px;"><h3>Note</h3><p style="margin: 0; font-size: 15px;">{{ $count->note }}</p></div>
    @endif

    <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between;">
        <a href="{{ route('stock.count') }}" class="btn btn-primary"><x-icon name="clipboard" size="17" /> Nouveau comptage</a>
        @if ($next)
            <a href="{{ route('stock.count.show', $next) }}" class="btn btn-ghost">Comptage suivant ({{ $next->created_at->locale('fr')->isoFormat('D MMM') }})</a>
        @endif
    </div>
</div>
