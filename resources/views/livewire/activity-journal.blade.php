@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $tones = [
        'danger' => ['var(--color-danger)', 'var(--color-danger-bg)'],
        'warning' => ['#9A5B0C', 'var(--color-warning-bg)'],
        'info' => ['var(--color-primary)', 'var(--color-mint)'],
    ];
    $byDay = $logs->getCollection()->groupBy(fn ($l) => $l->created_at->toDateString());
@endphp

<div class="sc jr">
    @include('partials.stock-count-styles')
    <style>
        .jr-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 18px 0 16px; }
        .jr-kpi { background: #fff; border: 1px solid var(--color-border); border-radius: 16px; padding: 14px 16px; box-shadow: 0 1px 2px rgba(16,35,29,.04), 0 6px 18px -8px rgba(16,35,29,.08); min-width: 0; }
        .jr-kpi .k { font-size: 12.5px; font-weight: 600; color: var(--color-text-secondary); }
        .jr-kpi .v { font-size: 23px; font-weight: 800; margin-top: 4px; white-space: nowrap; }
        .jr-kpi .s { font-size: 12.5px; color: var(--color-text-secondary); margin-top: 2px; }
        .jr-filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 14px; }
        .jr-filters select { min-width: 190px; }
        .jr-day { font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-secondary); margin: 18px 2px 8px; }
        .jr-row { display: grid; grid-template-columns: 40px minmax(0, 1fr) auto; gap: 12px; align-items: start; padding: 13px 18px; border-top: 1px solid var(--color-border); }
        .jr-row:first-child { border-top: 0; }
        .jr-row.danger { background: linear-gradient(90deg, var(--color-danger-bg), #fff 55%); }
        .jr-ic { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; position: relative; }
        .jr-ic .me-avatar { position: absolute; right: -6px; bottom: -6px; border: 2px solid #fff; }
        .jr-txt { font-size: 14.5px; line-height: 1.45; min-width: 0; overflow-wrap: anywhere; }
        .jr-txt strong { font-weight: 700; }
        .jr-meta { font-size: 12.5px; color: var(--color-text-secondary); margin-top: 4px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .jr-badge { font-size: 11.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
        .jr-time { font-size: 13px; font-weight: 700; color: var(--color-text-secondary); white-space: nowrap; padding-top: 2px; }
        @media (max-width: 900px) { .jr-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) {
            .jr-row { padding: 12px 14px; grid-template-columns: 36px minmax(0, 1fr); }
            .jr-ic { width: 36px; height: 36px; }
            .jr-time { grid-column: 2; grid-row: 2; padding-top: 0; font-weight: 600; font-size: 12px; }
            .jr-filters select { min-width: 0; width: 100%; }
            .jr-filters .sc-seg { width: 100%; }
            .jr-filters .sc-seg button { flex: 1; }
            .jr-kpi .v { font-size: 20px; }
            .jr-kpi { padding: 12px 13px; }
        }
    </style>

    <div class="page-head">
        <div class="eyebrow">Sécurité</div>
        <h1>Journal des actions</h1>
        <p class="lead">Qui a fait quoi, et quand. Personne ne peut modifier ni effacer une ligne de ce journal, pas même le patron.</p>
    </div>

    <div class="jr-kpis">
        <div class="jr-kpi">
            <div class="k">Actions à surveiller</div>
            <div class="v" style="color: {{ $stats['sensitive'] ? '#9A5B0C' : 'inherit' }};">{{ $stats['sensitive'] }}</div>
            <div class="s">annulations, remises, retraits, prix</div>
        </div>
        <div class="jr-kpi">
            <div class="k">Bons annulés</div>
            <div class="v" style="color: {{ $stats['cancelled'] ? 'var(--color-danger)' : 'inherit' }};">{{ $stats['cancelled'] }}</div>
            <div class="s">{{ $fcfa($stats['cancelled_amount']) }} F au total</div>
        </div>
        <div class="jr-kpi">
            <div class="k">Remises et offerts</div>
            <div class="v">{{ $fcfa($stats['gestures_amount']) }} <small style="font-size: 13px; color: var(--color-text-secondary);">F</small></div>
            <div class="s">donnés aux clients</div>
        </div>
        <div class="jr-kpi">
            <div class="k">Connexions</div>
            <div class="v">{{ $stats['logins'] }}</div>
            <div class="s">sur la période</div>
        </div>
    </div>

    <div class="jr-filters">
        <div class="sc-seg">
            @foreach ($periods as $key => $label)
                <button type="button" class="{{ $period === $key ? 'on' : '' }}" wire:click="setPeriod('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
        <select wire:model.live="group" aria-label="Type d'action">
            <option value="">Toutes les actions</option>
            @foreach ($groups as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="userId" aria-label="Personne">
            <option value="">Toute l'équipe</option>
            @foreach ($people as $p)
                <option value="{{ $p->id }}">{{ $p->first_name }} {{ $p->last_name }}{{ $p->left_at ? ', ancien' : '' }}</option>
            @endforeach
        </select>
    </div>

    @forelse ($byDay as $day => $items)
        @php $d = \Carbon\Carbon::parse($day)->locale('fr'); @endphp
        <div class="jr-day">{{ $d->isToday() ? "Aujourd'hui" : ($d->isYesterday() ? 'Hier' : ucfirst($d->isoFormat('dddd D MMMM'))) }}</div>
        <div class="sc-list">
            @foreach ($items as $log)
                @php [$fg, $bg] = $tones[$log->severity()]; @endphp
                <div class="jr-row {{ $log->severity() === 'danger' ? 'danger' : '' }}">
                    <div class="jr-ic" style="background: {{ $bg }}; color: {{ $fg }};">
                        <x-icon :name="$log->icon()" size="19" />
                        <x-avatar :user="$log->user" :name="$log->user_name" size="20" />
                    </div>
                    <div class="jr-txt">
                        <strong>{{ $log->who() }}</strong> {{ $log->description }}
                        <div class="jr-meta">
                            <span class="jr-badge" style="background: {{ $bg }}; color: {{ $fg }};">{{ $log->label() }}</span>
                        </div>
                    </div>
                    <div class="jr-time">{{ $log->created_at->format('H\hi') }}</div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="sc-card" style="text-align: center; padding: 32px 18px;">
            <x-icon name="history" size="30" style="color: var(--color-text-secondary);" />
            <p style="margin: 10px 0 0; font-size: 15px;">Aucune action sur cette période.</p>
        </div>
    @endforelse

    @if ($logs->hasPages())
        <div style="margin-top: 14px;">{{ $logs->links() }}</div>
    @endif
</div>
