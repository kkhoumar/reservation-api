@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $tile = 'padding: 14px 16px;';
    $small = 'font-size: 12px; color: var(--color-text-secondary);';
    $big = 'font-size: 22px; font-weight: 800; letter-spacing: -0.01em;';
@endphp

<div>
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
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px;">
        <div class="card" style="background: var(--color-primary); border-color: var(--color-primary); color: white;">
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

        <div class="card">
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
