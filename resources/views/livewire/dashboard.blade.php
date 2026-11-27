@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $tile = 'padding: 14px 16px;';
    $small = 'font-size: 12px; color: var(--color-text-secondary);';
    $big = 'font-size: 22px; font-weight: 800; letter-spacing: -0.01em;';
@endphp

<div>
    <x-flash />
    @if ($noRestaurant)
        <div class="card">Choisissez d'abord un maquis.</div>
    @else
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Vue d'ensemble
            </div>
            <h1>Tableau de bord</h1>
        </div>

        <div style="display: flex; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; padding: 3px;">
            @foreach ($periods as $key => [$label])
                <button wire:click="setPeriod('{{ $key }}')"
                        style="border: none; background: {{ $period === $key ? 'var(--color-primary)' : 'transparent' }};
                               color: {{ $period === $key ? 'white' : 'var(--color-text-secondary)' }};
                               padding: 7px 14px; border-radius: 7px; font-size: 13px; font-weight: 600;
                               font-family: inherit; cursor: pointer;">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ===== Pour bien démarrer (nouveau maquis) ===== --}}
    @if ($setup)
        @php $doneCount = collect($setup)->where('done', true)->count(); $next = collect($setup)->firstWhere('done', false); @endphp
        <div class="card ob">
            <div class="ob-head">
                <div>
                    <h3>Pour bien démarrer</h3>
                    <p>{{ $doneCount }} étape{{ $doneCount > 1 ? 's' : '' }} sur {{ count($setup) }} · ce guide disparaît quand tout est fait.</p>
                </div>
                <div class="ob-bar" aria-hidden="true"><i style="width: {{ round($doneCount / count($setup) * 100) }}%"></i></div>
            </div>
            <ol class="ob-steps">
                @foreach ($setup as $i => $step)
                    <li class="{{ $step['done'] ? 'done' : ($step === $next ? 'next' : '') }}">
                        <span class="ob-dot">@if ($step['done'])<x-icon name="check" size="16" stroke="2.6" />@else{{ $i + 1 }}@endif</span>
                        <span class="ob-txt">
                            <strong>{{ $step['title'] }}</strong>
                            <small>{{ $step['done'] ? 'Fait' : $step['text'] }}</small>
                            @if ($step['key'] === 'tables' && ! $step['done'] && $canSkipTables)
                                <button type="button" class="ob-skip" wire:click="skipTables" wire:confirm="Pas de tables : vous vendrez en vente rapide (comptoir, à emporter). Vous pourrez ajouter des tables plus tard dans les Paramètres. Continuer ?">Je n'ai pas de tables</button>
                            @endif
                        </span>
                        @if (! $step['done'])
                            <a href="{{ $step['url'] }}" class="btn {{ $step === $next ? 'btn-primary' : 'btn-ghost' }}">{{ $step['cta'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
        <style>
            .ob { margin-bottom: 24px; border: 1.5px solid var(--color-primary-300); }
            .ob-head { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 12px; }
            .ob-head h3 { margin: 0; font-size: 18px; }
            .ob-head p { margin: 2px 0 0; font-size: 13px; color: var(--color-text-secondary); }
            .ob-bar { flex: 0 1 200px; height: 8px; border-radius: 99px; background: var(--color-border); overflow: hidden; }
            .ob-bar i { display: block; height: 100%; background: var(--color-primary-300); border-radius: 99px; }
            .ob-steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
            .ob-steps li { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; background: var(--color-bg); }
            .ob-steps li.next { background: var(--color-mint); }
            .ob-steps li.done { opacity: .7; }
            .ob-dot { width: 30px; height: 30px; flex: none; border-radius: 50%; display: grid; place-items: center; font-weight: 800; font-size: 14px; background: #fff; border: 1.5px solid var(--color-border); color: var(--color-text-secondary); }
            .ob-steps li.next .ob-dot { border-color: var(--color-primary); color: var(--color-primary); }
            .ob-steps li.done .ob-dot { background: var(--color-success); border-color: var(--color-success); color: #fff; }
            .ob-txt { flex: 1; min-width: 0; display: flex; flex-direction: column; }
            .ob-txt strong { font-size: 15px; }
            .ob-txt small { font-size: 12.5px; color: var(--color-text-secondary); }
            .ob-steps .btn { white-space: nowrap; padding: 8px 14px; font-size: 13.5px; text-decoration: none; }
            .ob-skip { align-self: flex-start; margin-top: 4px; padding: 6px 0; min-height: 32px; border: 0; background: none; font: inherit; font-size: 13px; font-weight: 700;
                color: var(--color-accent); text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
            @media (max-width: 640px) {
                .ob-steps li { flex-wrap: wrap; }
                .ob-steps .btn { width: 100%; justify-content: center; min-height: 44px; }
            }
        </style>
    @endif

    {{-- ===== Fin du guide : « Bravo », une seule fois ===== --}}
    @if ($celebrate)
        <div class="card ob-bravo" role="status" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 9000)"
             x-transition:leave="ob-bravo-leave" x-transition:leave-start="ob-bravo-from" x-transition:leave-end="ob-bravo-to">
            <span class="ob-bravo-ic" aria-hidden="true">🎉</span>
            <div class="ob-bravo-txt">
                <strong>Bravo, votre maquis est prêt !</strong>
                <span>Tout est en place pour vendre et suivre votre argent. Bonnes ventes !</span>
            </div>
            <button type="button" class="ob-bravo-x" @click="show = false" aria-label="Fermer"><x-icon name="x" size="18" /></button>
        </div>
        <style>
            .ob-bravo { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; background: var(--color-mint); border: 1.5px solid var(--color-primary-300);
                animation: ob-bravo-in .35s ease-out both; }
            .ob-bravo-ic { font-size: 34px; line-height: 1; flex: none; }
            .ob-bravo-txt { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
            .ob-bravo-txt strong { font-size: 17px; }
            .ob-bravo-txt span { font-size: 13.5px; color: var(--color-text-secondary); }
            .ob-bravo-x { flex: none; width: 40px; height: 40px; display: grid; place-items: center; border: 0; border-radius: 50%; background: transparent; cursor: pointer; color: var(--color-text-secondary); }
            .ob-bravo-leave { transition: opacity .3s ease, transform .3s ease; }
            .ob-bravo-from { opacity: 1; }
            .ob-bravo-to { opacity: 0; transform: translateY(-6px); }
            @keyframes ob-bravo-in { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }
            @media (prefers-reduced-motion: reduce) { .ob-bravo { animation: none; } .ob-bravo-leave { transition: none; } }
            @media (max-width: 400px) { .ob-bravo { gap: 10px; padding: 14px; } .ob-bravo-ic { font-size: 28px; } .ob-bravo-txt strong { font-size: 16px; } }
        </style>
    @endif

    {{-- ===== En ce moment ===== --}}
    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--color-text-secondary); margin-bottom: 8px;">En ce moment</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 24px;">
        <a href="{{ route('cash-register.manage') }}" class="card" style="{{ $tile }} text-decoration: none; color: inherit; border-left: 4px solid {{ $register ? 'var(--color-primary-300)' : 'var(--color-border)' }};">
            <div style="{{ $small }}">Caisse</div>
            @if ($register)
                <div style="{{ $big }}">{{ $fcfa($drawerAmount) }}</div>
                <div style="{{ $small }}">dans le tiroir · ouverte par {{ $register->openedByUser?->first_name }} à {{ $register->opened_at->format('H:i') }}</div>
            @else
                <div style="{{ $big }} color: var(--color-text-secondary);">Fermée</div>
                <div style="{{ $small }}">Ouvrir la caisse →</div>
            @endif
        </a>

        @if ($hasProducts)
            @php
                $late = $stockCountDays === null || $stockCountDays > \App\Models\StockCount::REMIND_AFTER_DAYS;
                $stockAgo = match (true) {
                    $stockCountDays === null => 'Jamais',
                    $stockCountDays === 0 => "Aujourd'hui",
                    $stockCountDays === 1 => 'Hier',
                    default => "Il y a {$stockCountDays} j",
                };
            @endphp
            <a href="{{ route('stock.count') }}" class="card" style="{{ $tile }} text-decoration: none; color: inherit; border-left: 4px solid {{ $late ? 'var(--color-danger)' : ($stockCountDays > 3 ? 'var(--color-warning)' : 'var(--color-primary-300)') }};">
                <div style="{{ $small }}">Stock compté</div>
                <div style="{{ $big }} color: {{ $late ? 'var(--color-danger)' : 'inherit' }};">{{ $stockAgo }}</div>
                <div style="{{ $small }}">{{ $late ? 'Comptez le frigo et les casiers' : 'Voir le comptage' }}</div>
            </a>
        @endif

        @if ($showCounter)
            <a href="{{ route('counter') }}" class="card" style="{{ $tile }} text-decoration: none; color: inherit; border-left: 4px solid {{ $pendingCount > 0 ? 'var(--color-accent)' : 'var(--color-border)' }};">
                <div style="{{ $small }}">Au comptoir</div>
                <div style="{{ $big }}">{{ $pendingCount }} bon{{ $pendingCount > 1 ? 's' : '' }}</div>
                <div style="{{ $small }}">{{ $pendingCount > 0 ? $fcfa($pendingTotal) . ' FCFA à encaisser' : 'rien à encaisser' }}</div>
            </a>
        @endif

        @if ($totalTables > 0)
            <a href="{{ route('tables.index') }}" class="card" style="{{ $tile }} text-decoration: none; color: inherit;">
                <div style="{{ $small }}">Tables occupées</div>
                <div style="{{ $big }}">{{ $occupiedTables }} <span style="font-size: 14px; color: var(--color-text-secondary);">/ {{ $totalTables }}</span></div>
                <div style="background: var(--color-border); border-radius: 999px; height: 6px; overflow: hidden; margin-top: 6px;">
                    <div style="background: var(--color-accent); height: 100%; width: {{ ($occupiedTables / $totalTables) * 100 }}%;"></div>
                </div>
            </a>
        @endif

        @if ($creditEnabled)
            <a href="{{ route('credits') }}" class="card" style="{{ $tile }} text-decoration: none; color: inherit; border-left: 4px solid {{ $creditDue > 0 ? 'var(--color-accent)' : 'var(--color-border)' }};">
                <div style="{{ $small }}">Crédits à récupérer</div>
                <div style="{{ $big }} color: {{ $creditDue > 0 ? 'var(--color-accent)' : 'inherit' }};">{{ $fcfa($creditDue) }}</div>
                <div style="{{ $small }}">dus par les clients</div>
            </a>
        @endif

        @if ($sacoches > 0)
            <a href="{{ route('sacoches') }}" class="card" style="{{ $tile }} text-decoration: none; color: inherit; border-left: 4px solid var(--color-accent);">
                <div style="{{ $small }}">Sacoches non versées</div>
                <div style="{{ $big }} color: var(--color-accent);">{{ $fcfa($sacoches) }}</div>
                <div style="{{ $small }}">encore sur les serveuses</div>
            </a>
        @endif
    </div>

    {{-- ===== Sur la période ===== --}}
    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--color-text-secondary); margin-bottom: 8px;">{{ $periods[$period][0] }}</div>
    <div class="dash-kpis" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px;">
        <div class="card dash-wide" style="background: var(--color-primary); border-color: var(--color-primary); color: white;">
            <div style="font-size: 13px; color: rgba(255,255,255,0.75);">Chiffre d'affaires</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 4px;">{{ $fcfa($revenue) }} <span style="font-size: 14px; color: rgba(255,255,255,0.7);">FCFA</span></div>
            <div style="font-size: 12px; margin-top: 4px; color: rgba(255,255,255,0.85);">
                @if ($previousRevenue > 0)
                    @php $pct = (int) round(($revenue - $previousRevenue) / $previousRevenue * 100); @endphp
                    <strong>{{ $pct >= 0 ? '▲ +' : '▼ ' }}{{ $pct }} %</strong> par rapport à {{ $periods[$period][1] }} ({{ $fcfa($previousRevenue) }})
                @else
                    Aucune vente {{ $periods[$period][1] }}
                @endif
            </div>
            @if ($revenue > 0)
                <div style="font-size: 12px; margin-top: 10px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.2); line-height: 1.7; color: rgba(255,255,255,0.85);">
                    Espèces {{ $fcfa($sales->cash) }}
                    @if ($sales->mobileTotal() > 0) · Mobile Money {{ $fcfa($sales->mobileTotal()) }} @endif
                    @if ($sales->credit > 0) · Crédit {{ $fcfa($sales->credit) }} @endif
                </div>
            @endif
        </div>

        <div class="card">
            <div style="font-size: 13px; color: var(--color-text-secondary);">Bons réglés</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 4px;">{{ $orderCount }}</div>
            <div style="{{ $small }}">Panier moyen : <strong>{{ $fcfa($averageTicket) }} FCFA</strong> par bon</div>
        </div>

        <div class="card">
            <div style="font-size: 13px; color: var(--color-text-secondary);">Dépenses</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 4px; color: {{ $expenses > 0 ? 'var(--color-danger)' : 'inherit' }};">{{ $fcfa($expenses) }}</div>
            @foreach ($expensesByCategory as $row)
                <div style="{{ $small }}">{{ \App\Models\Expense::CATEGORIES[$row->category] ?? $row->category }} : {{ $fcfa($row->total) }}</div>
            @endforeach
        </div>

        <div class="card dash-wide">
            <div style="font-size: 13px; color: var(--color-text-secondary);">Bénéfice estimé</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 4px; color: {{ $profit < 0 ? 'var(--color-danger)' : 'var(--color-primary)' }};">{{ $fcfa($profit) }}</div>
            <div style="{{ $small }} line-height: 1.6; margin-top: 4px;">
                Ventes {{ $fcfa($revenue) }}<br>
                − prix d'achat des produits vendus {{ $fcfa($costOfGoods) }}<br>
                − autres dépenses {{ $fcfa($otherExpenses) }}
                @if ($stockLosses !== 0)<br>− pertes de stock {{ $fcfa($stockLosses) }}@endif
            </div>
            @if ($uncostedQuantity > 0)
                <div style="font-size: 12px; color: var(--color-accent); margin-top: 6px;">
                    <x-icon name="alert" size="15" style="color: var(--color-warning);" /> {{ $uncostedQuantity }} {{ $uncostedQuantity > 1 ? 'articles vendus' : 'article vendu' }} sans prix d'achat : le bénéfice affiché est trop haut.
                    <a href="{{ route('products.manage') }}" style="color: var(--color-accent);">Renseigner les prix</a>
                </div>
            @endif
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-top: 16px; align-items: start;">
        <div class="card">
            <h3 style="margin: 0 0 4px 0;">Chiffre d'affaires des 7 derniers jours</h3>
            <div style="{{ $small }} margin-bottom: 16px;">Espèces, Mobile Money et crédit compris</div>
            @php $maxValue = collect($chartData)->max('value') ?: 1; @endphp
            <div style="display: flex; align-items: flex-end; gap: 8px; height: 170px;">
                @foreach ($chartData as $day)
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 4px;">
                        <div style="font-size: 11px; color: var(--color-text-secondary); white-space: nowrap;">{{ $day['value'] > 0 ? $fcfa($day['value']) : '' }}</div>
                        <div title="{{ $fcfa($day['value']) }} FCFA" style="width: 70%; max-width: 46px; border-radius: 6px 6px 0 0; background: {{ $loop->last ? 'var(--color-primary)' : 'var(--color-accent-light)' }}; height: {{ max(3, ($day['value'] / $maxValue) * 120) }}px;"></div>
                        <div style="font-size: 11px; color: var(--color-text-secondary);">{{ $day['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h3 style="margin: 0 0 10px 0;">Qui a vendu</h3>
            @forelse ($stats->byServer as $row)
                <div style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                    <span>{{ $row->name }} <span style="{{ $small }}">· {{ $row->bons }} bon{{ $row->bons > 1 ? 's' : '' }}</span></span>
                    <strong>{{ $fcfa($row->amount) }}</strong>
                </div>
            @empty
                <p style="{{ $small }}">Aucune vente sur la période.</p>
            @endforelse
        </div>
    </div>

    {{-- Cuisine et bar séparés : chaque produit vendu et le total de chaque famille --}}
    <div class="kinds" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px; align-items: start;">
        @foreach ($stats->byKind as $group)
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-bottom: 6px;">
                    <h3 style="margin: 0;">{{ $group->label }}</h3>
                    <strong style="font-size: 20px; color: var(--color-primary);">{{ $fcfa($group->amount) }}</strong>
                </div>
                <div style="{{ $small }} margin-bottom: 8px;">
                    {{ $group->quantity }} vendu{{ $group->quantity > 1 ? 's' : '' }}
                    @if ($group->margin !== null && $group->amount > 0) · bénéfice {{ $fcfa($group->margin) }} @endif
                </div>
                @forelse ($group->items as $item)
                    <div style="display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                        <span>{{ $item->name }} <span style="{{ $small }}">× {{ $item->quantity }}</span></span>
                        <strong>{{ $fcfa($item->amount) }}</strong>
                    </div>
                @empty
                    <p style="{{ $small }}">Rien de vendu sur la période.</p>
                @endforelse
            </div>
        @endforeach
    </div>
    <style>
        @media (max-width: 760px) { .kinds { grid-template-columns: minmax(0, 1fr) !important; } }
        /* Téléphone : « Bons réglés » et « Dépenses » côte à côte, les grands chiffres sur toute la largeur */
        @media (max-width: 640px) {
            main div.dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 10px !important; }
            .dash-kpis > .card { padding: 14px !important; min-width: 0; }
            .dash-kpis > .dash-wide { grid-column: 1 / -1; }
            .dash-kpis > .card [style*="font-size: 28px"] { font-size: 24px !important; overflow-wrap: anywhere; }
        }
    </style>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; align-items: start;">
        <div class="card">
            <h3 style="margin: 0 0 10px 0;">Ce qui se vend le plus</h3>
            @forelse ($stats->topProducts as $i => $product)
                <div style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                    <span><span style="{{ $small }}">{{ $i + 1 }}.</span> {{ $product->name }} <span style="{{ $small }}">× {{ $product->quantity }}</span></span>
                    <strong>{{ $fcfa($product->amount) }}</strong>
                </div>
            @empty
                <p style="{{ $small }}">Aucune vente sur la période.</p>
            @endforelse
        </div>

        <div class="card">
            <h3 style="margin: 0 0 10px 0;">À surveiller</h3>
            @php $nothing = $stats->gesturesTotal === 0 && $stats->cancelledCount === 0 && $closingsWithGap === 0 && $stockLosses <= 0; @endphp

            <div style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                <span>Pertes et écarts de stock <span style="{{ $small }}">· casse, périmé, inventaire</span></span>
                <a href="{{ route('stock.index') }}" style="text-decoration: none; color: inherit;">
                    <strong style="color: {{ $stockLosses > 0 ? 'var(--color-danger)' : 'inherit' }};">{{ $fcfa(max(0, $stockLosses)) }}</strong>
                </a>
            </div>

            <div style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                <span>Offerts et remises <span style="{{ $small }}">· {{ $stats->gestures->count() }}</span></span>
                <strong style="color: {{ $stats->gesturesTotal > 0 ? 'var(--color-accent)' : 'inherit' }};">{{ $fcfa($stats->gesturesTotal) }}</strong>
            </div>
            @foreach ($stats->gestures->take(3) as $g)
                <div style="{{ $small }} padding: 0 0 4px 10px;">• {{ $g->label }} par {{ $g->by }} ({{ collect([$g->reason, $g->where])->filter()->join(', ') }})</div>
            @endforeach

            <div style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                <span>Bons annulés <span style="{{ $small }}">· {{ $stats->cancelledCount }}</span></span>
                <strong style="color: {{ $stats->cancelledCount > 0 ? 'var(--color-danger)' : 'inherit' }};">{{ $fcfa($stats->cancelledTotal) }}</strong>
            </div>

            <div style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                <span>Écarts de caisse <span style="{{ $small }}">· {{ $closingsWithGap }} clôture{{ $closingsWithGap > 1 ? 's' : '' }} sur {{ $closingsCount }} avec un écart</span></span>
                <strong style="color: {{ $closingsGapTotal < 0 ? 'var(--color-danger)' : 'inherit' }};">{{ $closingsGapTotal > 0 ? '+' : '' }}{{ $fcfa($closingsGapTotal) }}</strong>
            </div>

            @if ($nothing)
                <div style="{{ $small }} margin-top: 6px;">Rien d'anormal sur la période.</div>
            @endif
        </div>
    </div>

    @if ($lowStockProducts->isNotEmpty())
        <div class="card" style="margin-top: 16px; border-left: 4px solid var(--color-danger);">
            <strong style="font-size: 14px;">{{ $lowStockProducts->count() }} produit{{ $lowStockProducts->count() > 1 ? 's' : '' }} bientôt en rupture</strong>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                @foreach ($lowStockProducts as $product)
                    <span style="background: var(--color-danger-bg); color: var(--color-danger); padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                        {{ $product->name }} : {{ $product->stock_quantity }} restant{{ $product->stock_quantity > 1 ? 's' : '' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
    @endif
</div>
