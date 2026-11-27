<x-layouts.app>
    <div class="ex">
        <div style="margin-bottom: 18px;">
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Pour le patron et le comptable
            </div>
            <h1>Exports</h1>
            <p class="ex-intro">Téléchargez vos chiffres en fichier <strong>Excel</strong>. Il s'ouvre sur l'ordinateur comme sur le téléphone (Excel, Google Sheets, WPS).</p>
        </div>

        {{-- Période --}}
        @php
            $shortcuts = [
                'Ce mois' => [now()->startOfMonth(), now()],
                'Mois dernier' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                'Cette année' => [now()->startOfYear(), now()],
            ];
        @endphp
        <div class="card ex-period">
            <div class="ex-chips">
                @foreach ($shortcuts as $label => [$a, $b])
                    @php $on = $a->toDateString() === $from && $b->toDateString() === $to; @endphp
                    <a href="{{ route('exports.index', ['from' => $a->toDateString(), 'to' => $b->toDateString()]) }}" class="ex-chip {{ $on ? 'on' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('exports.index') }}" class="ex-dates">
                <label>Du <input type="date" name="from" value="{{ $from }}"></label>
                <label>Au <input type="date" name="to" value="{{ $to }}"></label>
                <button type="submit" class="btn btn-ghost">Changer</button>
            </form>
        </div>

        {{-- Tout en un fichier --}}
        <div class="card ex-all">
            <div class="ex-all-txt">
                <strong>Toute la période en un seul fichier</strong>
                <span>Un <b>Résumé</b> (ventes, dépenses, bénéfice, caisse, crédits), puis un onglet par sujet. Idéal pour le comptable.</span>
                <span class="ex-when">Du {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
            </div>
            <a href="{{ route('exports.download', ['type' => 'tout', 'from' => $from, 'to' => $to]) }}" class="btn btn-primary ex-all-btn">
                <x-icon name="download" size="18" /> Tout télécharger (Excel)
            </a>
        </div>

        <h2 class="ex-h2">Ou un seul fichier</h2>
        <div class="ex-grid">
            @foreach ($exports as $type => [$label, $description])
                <div class="card ex-card">
                    <div>
                        <h3>{{ $label }}</h3>
                        <p>{{ $description }}</p>
                    </div>
                    <div class="ex-actions">
                        <a href="{{ route('exports.download', ['type' => $type, 'from' => $from, 'to' => $to]) }}" class="btn btn-secondary ex-dl">
                            <x-icon name="download" size="16" /> Excel
                        </a>
                        <a href="{{ route('exports.download', ['type' => $type, 'from' => $from, 'to' => $to, 'format' => 'csv']) }}" class="ex-csv" title="Pour les logiciels de comptabilité">CSV</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .ex-intro { font-size: 14px; color: var(--color-text-secondary); margin: 6px 0 0; }
        .ex-period { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .ex-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .ex-chip { display: inline-flex; align-items: center; min-height: 40px; padding: 0 14px; border-radius: 999px; border: 1.5px solid var(--color-border);
            background: #fff; color: var(--color-text); font-size: 14px; font-weight: 700; text-decoration: none; }
        .ex-chip.on { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
        .ex-dates { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
        .ex-dates label { display: flex; flex-direction: column; gap: 4px; font-size: 12.5px; font-weight: 700; }
        .ex-dates input { min-height: 40px; }
        .ex-dates .btn { min-height: 40px; }
        .ex-all { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; border: 1.5px solid var(--color-primary-300); background: var(--color-mint); }
        .ex-all-txt { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .ex-all-txt strong { font-size: 17px; }
        .ex-all-txt span { font-size: 13.5px; color: var(--color-text-secondary); }
        .ex-all-txt .ex-when { font-weight: 700; color: var(--color-primary); }
        .ex-all-btn { flex: none; min-height: 48px; padding: 0 20px; gap: 8px; text-decoration: none; white-space: nowrap; }
        .ex-h2 { font-size: 15px; margin: 0 0 10px; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .04em; }
        .ex-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; }
        .ex-card { display: flex; flex-direction: column; justify-content: space-between; gap: 12px; }
        .ex-card h3 { margin: 0 0 4px; font-size: 16px; }
        .ex-card p { margin: 0; font-size: 13px; color: var(--color-text-secondary); }
        .ex-actions { display: flex; align-items: center; gap: 10px; }
        .ex-dl { flex: 1; justify-content: center; gap: 6px; min-height: 44px; text-decoration: none; }
        .ex-csv { min-height: 44px; display: inline-flex; align-items: center; padding: 0 10px; font-size: 13px; font-weight: 700; color: var(--color-text-secondary); text-decoration: underline; text-underline-offset: 3px; }
        .ex .ex-chips { align-items: center; }
        .ex .ex-chip { height: 40px; min-height: 40px; flex: 0 0 auto; justify-content: center; white-space: nowrap; line-height: 1; }
        @media (max-width: 640px) {
            .ex-period { flex-direction: column; align-items: stretch; }
            .ex .ex-chips { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
            .ex .ex-chip { padding: 0 6px; font-size: 13.5px; min-width: 0; }
            .ex-dates { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
            .ex-dates label { min-width: 0; }
            .ex-dates input { width: 100%; min-width: 0; }
            .ex-dates .btn { grid-column: 1 / -1; justify-content: center; }
            .ex-all { flex-direction: column; align-items: stretch; }
            .ex-all-btn { justify-content: center; white-space: normal; }
        }
    </style>
</x-layouts.app>
