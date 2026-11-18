<div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Vue d'ensemble
            </div>
            <h1>Tableau de bord</h1>
        </div>

        <div style="display: flex; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; padding: 3px;">
            @foreach (['today' => "Aujourd'hui", 'week' => 'Semaine', 'month' => 'Mois'] as $key => $label)
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

    {{-- Ligne KPI --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="color: var(--color-text-secondary); font-size: 13px; font-weight: 500;">Chiffre d'affaires</div>
                <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(6,59,39,0.08); display: flex; align-items: center; justify-content: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#063B27" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; letter-spacing: -0.01em; margin-top: 10px;">
                {{ number_format($revenue, 0, ',', ' ') }} <span style="font-size: 14px; font-weight: 600; color: var(--color-text-secondary);">FCFA</span>
            </div>
            @isset($sales)
                @if ($sales->total() > 0)
                    <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 6px; line-height: 1.6;">
                        Espèces {{ number_format($sales->cash, 0, ',', ' ') }}
                        @if ($sales->mobileTotal() > 0) · Mobile Money {{ number_format($sales->mobileTotal(), 0, ',', ' ') }} @endif
                        @if ($sales->credit > 0) · <span style="color: var(--color-accent);">Ardoise {{ number_format($sales->credit, 0, ',', ' ') }}</span> @endif
                    </div>
                @endif
            @endisset
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="color: var(--color-text-secondary); font-size: 13px; font-weight: 500;">Commandes</div>
                <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(255,122,0,0.1); display: flex; align-items: center; justify-content: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FF7A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2 6 6H2v2h1l1.5 10.5A2 2 0 0 0 6.5 20h11a2 2 0 0 0 2-1.5L21 8h-1"/><path d="M9 2h6l1 4H8z"/></svg>
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; letter-spacing: -0.01em; margin-top: 10px;">
                {{ $orderCount }}
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="color: var(--color-text-secondary); font-size: 13px; font-weight: 500;">Dépenses</div>
                <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(232,74,58,0.1); display: flex; align-items: center; justify-content: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E84A3A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; letter-spacing: -0.01em; margin-top: 10px; color: var(--color-danger);">
                {{ number_format($expenses, 0, ',', ' ') }} <span style="font-size: 14px; font-weight: 600; color: var(--color-text-secondary);">FCFA</span>
            </div>
        </div>

        <div class="card" style="background: var(--color-primary); border-color: var(--color-primary);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="color: rgba(255,255,255,0.7); font-size: 13px; font-weight: 500;">Bénéfice estimé</div>
                <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(255,255,255,0.12); display: flex; align-items: center; justify-content: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 800; letter-spacing: -0.01em; margin-top: 10px; color: white;">
                {{ number_format($profit, 0, ',', ' ') }} <span style="font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.65);">FCFA</span>
            </div>
        </div>

    </div>


  @if ($lowStockProducts->isNotEmpty())
    <div class="card" style="margin-top: 16px; border-left: 4px solid var(--color-danger);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D93B3B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg>
            <strong style="font-size: 14px;">{{ $lowStockProducts->count() }} produit{{ $lowStockProducts->count() > 1 ? 's' : '' }} en stock faible</strong>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            @foreach ($lowStockProducts as $product)
                <span style="background: rgba(217,59,59,0.1); color: #D93B3B; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                    {{ $product->name }} ({{ $product->stock_quantity }})
                </span>
            @endforeach
        </div>
    </div>
@endif


    {{-- Deuxième ligne : graphique + tables --}}
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-top: 16px;">

        <div class="card">
            <h3 style="margin: 0 0 4px 0;">Évolution du chiffre d'affaires</h3>
            <div style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 20px;">7 derniers jours</div>

            @php
                $maxValue = collect($chartData)->max('value') ?: 1;
            @endphp

            <svg width="100%" height="180" viewBox="0 0 560 180" preserveAspectRatio="none" style="overflow: visible;">
                <defs>
                    <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FF7A00"/>
                        <stop offset="100%" stop-color="#FFA62B"/>
                    </linearGradient>
                </defs>

                @for ($i = 0; $i <= 3; $i++)
                    <line x1="0" y1="{{ 10 + $i * 40 }}" x2="560" y2="{{ 10 + $i * 40 }}" stroke="#E5E7EB" stroke-width="1"/>
                @endfor

                @php $barWidth = 560 / count($chartData); @endphp

                @foreach ($chartData as $i => $day)
                    @php
                        $barHeight = max(3, ($day['value'] / $maxValue) * 130);
                        $x = $i * $barWidth + $barWidth * 0.22;
                        $w = $barWidth * 0.56;
                        $y = 140 - $barHeight;
                    @endphp
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $barHeight }}" rx="4" fill="url(#barGradient)"/>
                    <text x="{{ $x + $w/2 }}" y="165" text-anchor="middle" font-size="11" fill="#6B7280" font-family="Inter">{{ $day['label'] }}</text>
                @endforeach
            </svg>
        </div>

        <div class="card">
            <h3 style="margin: 0 0 16px 0;">Tables</h3>

            <div style="display: flex; align-items: baseline; gap: 6px; margin-bottom: 6px;">
                <div style="font-size: 32px; font-weight: 800;">{{ $occupiedTables }}</div>
                <div style="font-size: 16px; color: var(--color-text-secondary);">/ {{ $totalTables }} occupées</div>
            </div>

            <div style="background: var(--color-border); border-radius: 999px; height: 8px; overflow: hidden; margin-bottom: 16px;">
                <div style="background: var(--color-accent); height: 100%; width: {{ $totalTables > 0 ? ($occupiedTables / $totalTables) * 100 : 0 }}%;"></div>
            </div>

            <a href="{{ route('tables.index') }}" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                Voir le plan des tables
            </a>
        </div>

    </div>
</div>