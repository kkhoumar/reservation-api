@php
    $fcfa = fn ($v) => number_format((int) $v, 0, ',', ' ');
    $phoneFmt = fn ($p) => $p ? trim(chunk_split(\App\Models\User::normalizePhone($p), 2, ' ')) : null;
    $tones = ['retard' => 'danger', 'bientot' => 'accent', 'ajour' => 'primary', 'essai' => 'info', 'bloques' => 'muted'];
@endphp

<div class="subs">
    <style>
        .subs { --info: #2563EB; --muted: #4B5563; }
        .subs .top { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .subs .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .subs .kpi { background: var(--color-surface, #fff); border: 1px solid var(--color-border); border-radius: 16px; padding: 14px 16px; }
        .subs .kpi small { display: block; font-size: 12px; color: var(--color-text-secondary); font-weight: 600; }
        .subs .kpi strong { display: block; font-family: var(--font-display); font-size: 22px; margin-top: 4px; font-variant-numeric: tabular-nums; }
        .subs .kpi span { font-size: 12px; color: var(--color-text-secondary); }
        .subs .kpi.red strong { color: var(--color-danger); }

        .subs .bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
        .subs .tabs { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; flex: 1 1 auto; padding-bottom: 2px; }
        .subs .tabs::-webkit-scrollbar { display: none; }
        .subs .tab { flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 999px; border: 1px solid var(--color-border);
            background: var(--color-surface, #fff); font: inherit; font-size: 14px; font-weight: 600; color: var(--color-text); cursor: pointer; }
        .subs .tab .n { font-size: 12px; font-weight: 800; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; display: inline-grid; place-items: center; background: var(--color-bg); }
        .subs .tab.on { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
        .subs .tab.on .n { background: rgba(255,255,255,.2); color: #fff; }
        .subs .tab.t-retard .n { background: var(--color-danger-bg); color: var(--color-danger); }
        .subs .tab.t-retard.on { background: var(--color-danger); border-color: var(--color-danger); }
        .subs .tab.t-retard.on .n { background: rgba(255,255,255,.22); color: #fff; }
        .subs .search { flex: 0 1 280px; min-width: 200px; }
        .subs .search input { width: 100%; }

        .subs .table { background: var(--color-surface, #fff); border: 1px solid var(--color-border); border-radius: 16px; overflow: hidden; }
        .subs table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .subs th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--color-text-secondary); padding: 12px 14px; background: var(--color-bg); font-weight: 700; white-space: nowrap; }
        .subs td { padding: 14px; border-top: 1px solid var(--color-border); vertical-align: top; }
        .subs tr.g-retard td:first-child { box-shadow: inset 4px 0 0 var(--color-danger); }
        .subs tr.g-bientot td:first-child { box-shadow: inset 4px 0 0 var(--color-accent); }
        .subs .nm { font-weight: 800; }
        .subs .sub { display: block; font-size: 12px; color: var(--color-text-secondary); margin-top: 2px; }
        .subs .wa { color: #128C4A; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .subs .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .subs .pill.danger { background: var(--color-danger-bg); color: var(--color-danger); }
        .subs .pill.accent { background: var(--color-warning-bg, #FFF4E5); color: var(--color-accent-dark, #A84A1C); }
        .subs .pill.primary { background: var(--color-mint, #EAF4F0); color: var(--color-primary); }
        .subs .pill.info { background: #EAF1FF; color: var(--info); }
        .subs .pill.muted { background: #EEF0F2; color: var(--muted); }
        .subs .when { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .subs .when.danger { color: var(--color-danger); }
        .subs .when.accent { color: var(--color-accent-dark, #A84A1C); }
        .subs .acts { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: nowrap; }
        .subs .hist { font-size: 12px; color: var(--color-primary); font-weight: 700; text-decoration: none; }
        .subs .acts .btn { padding: 7px 12px; font-size: 13px; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
        .subs .empty { padding: 40px 20px; text-align: center; color: var(--color-text-secondary); }

        /* Téléphone : chaque abonné devient une carte */
        @media (max-width: 860px) {
            .subs table, .subs tbody, .subs tr, .subs td { display: block; width: 100%; }
            .subs thead { display: none; }
            .subs tr { padding: 14px 14px 12px; border-top: 1px solid var(--color-border); }
            .subs tr:first-child { border-top: none; }
            .subs tr.g-retard { box-shadow: inset 4px 0 0 var(--color-danger); }
            .subs tr.g-bientot { box-shadow: inset 4px 0 0 var(--color-accent); }
            .subs td { border: none; padding: 3px 0; }
            .subs tr td:first-child { box-shadow: none !important; }
            .subs td[data-l]::before { content: attr(data-l); display: inline-block; min-width: 128px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600; }
            .subs td .sub { display: inline; margin-left: 4px; }
            .subs td:first-child { padding-bottom: 6px; }
            .subs td:first-child .sub { display: block; margin-left: 0; }
            .subs .acts { justify-content: flex-start; margin-top: 8px; flex-wrap: wrap; }
            .subs .kpi strong { font-size: 19px; }
            .subs .kpi:first-child { grid-column: 1 / -1; }
            .subs td.no-label::before { display: none; }
            .subs .search { flex: 1 1 100%; }
        }
    </style>

    <div class="top">
        <div>
            <a href="{{ route('admin.restaurants') }}" style="font-size: 13px; color: var(--color-text-secondary); text-decoration: none;">← Tous les maquis</a>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin: 8px 0 4px 0;">Super Admin</div>
            <h1 style="margin: 0;">Abonnés</h1>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi red">
            <small>Impayés à récupérer</small>
            <strong>{{ $fcfa($lateAmount) }} F</strong>
            <span>{{ $counts['retard'] }} en retard · maquis coupés compris</span>
        </div>
        <div class="kpi">
            <small>À encaisser d'ici la fin du mois</small>
            <strong>{{ $fcfa($dueThisMonthAmount) }} F</strong>
            <span>{{ $dueThisMonthCount }} échéance{{ $dueThisMonthCount > 1 ? 's' : '' }}</span>
        </div>
        <div class="kpi">
            <small>En essai</small>
            <strong>{{ $counts['essai'] }}</strong>
            <span>à transformer en clients payants</span>
        </div>
    </div>

    <div class="bar">
        <div class="tabs" role="tablist" aria-label="Filtrer les abonnés">
            <button type="button" role="tab" class="tab {{ $tab === 'tous' ? 'on' : '' }}" wire:click="setTab('tous')">Tous <span class="n">{{ $counts['tous'] }}</span></button>
            @foreach ($groups as $key => $label)
                <button type="button" role="tab" class="tab t-{{ $key }} {{ $tab === $key ? 'on' : '' }}" wire:click="setTab('{{ $key }}')">{{ $label }} <span class="n">{{ $counts[$key] }}</span></button>
            @endforeach
        </div>
        <div class="search">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher : maquis, patron, téléphone…" aria-label="Rechercher un abonné">
        </div>
    </div>

    <div class="table">
        @if ($rows->isEmpty())
            <div class="empty">
                @if ($search !== '')
                    Aucun abonné ne correspond à « {{ $search }} ».
                @elseif ($tab === 'retard')
                    Aucun retard de paiement. 👍
                @else
                    Aucun abonné ici.
                @endif
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Maquis · patron</th>
                        <th>Formule</th>
                        <th>Dernier paiement</th>
                        <th>Prochaine échéance</th>
                        <th>État</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php
                            $group = $r->followUpGroup();
                            $owner = $r->users->first();
                            $pay = $r->lastSubscriptionPayment;
                            $end = $r->accessEndsAt();
                            // En jours du calendrier : échéance le 22, nous sommes le 24 → « en retard de 2 jours »
                            $days = $end ? (int) today()->diffInDays($end->copy()->startOfDay(), false) : null;
                            [$badge] = $r->subscriptionBadge();
                            $ownerWa = $owner?->phone ? 'https://wa.me/225' . \App\Models\User::normalizePhone($owner->phone) : null;
                            $remind = ! $r->isSuspended() && $end ? $r->reminderWhatsappUrl() : null;
                        @endphp
                        <tr class="g-{{ $group }}" wire:key="sub-{{ $r->id }}">
                            <td>
                                <span class="nm">{{ $r->name }}</span>
                                <span class="sub">
                                    {{ $owner ? trim($owner->first_name . ' ' . $owner->last_name) : 'Pas de patron actif' }}
                                    @if ($ownerWa)
                                        · <a class="wa" href="{{ $ownerWa }}" target="_blank" rel="noopener">{{ $phoneFmt($owner->phone) }}</a>
                                    @endif
                                </span>
                                <span class="sub">{{ $r->commune ?: '—' }} · {{ $r->paymentReference() }}</span>
                            </td>
                            <td data-l="Formule">
                                {{ $r->plan?->name ?? '—' }}
                                @if ($r->plan)<span class="sub">{{ $fcfa($r->plan->monthly_price) }} F/mois</span>@endif
                            </td>
                            <td data-l="Dernier paiement">
                                @if ($pay)
                                    {{ $pay->created_at->format('d/m/Y') }}
                                    <span class="sub">{{ $fcfa($pay->amount) }} F · {{ $methods[$pay->method] ?? $pay->method }}{{ $pay->months > 1 ? ' · ' . $pay->months . ' mois' : '' }}</span>
                                @else
                                    <span style="color: var(--color-text-secondary);">Jamais payé</span>
                                @endif
                                @if ($pay)
                                    <a class="hist sub" href="{{ route('admin.restaurants.subscription', $r->id) }}#historique">Voir l'historique →</a>
                                @endif
                            </td>
                            <td data-l="Prochaine échéance">
                                @if ($end)
                                    {{ $end->format('d/m/Y') }}
                                    @if ($r->subscription_status === 'trial')<span class="sub">fin de l'essai</span>@endif
                                    <span class="sub">
                                        @if ($days === null)
                                        @elseif ($days < 0)
                                            <span class="when danger">en retard de {{ -$days }} jour{{ -$days > 1 ? 's' : '' }}</span>
                                        @elseif ($days === 0)
                                            <span class="when accent">aujourd'hui</span>
                                        @else
                                            <span class="when {{ $days <= \App\Models\Restaurant::REMINDER_DAYS ? 'accent' : '' }}">dans {{ $days }} jour{{ $days > 1 ? 's' : '' }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span style="color: var(--color-text-secondary);">Sans échéance</span>
                                @endif
                            </td>
                            <td data-l="État">
                                <span class="pill {{ $tones[$group] }}">{{ $badge }}</span>
                                @if ($r->isInGracePeriod())
                                    <span class="sub">coupé le {{ $r->lockAt()->format('d/m') }} à {{ \App\Models\Restaurant::LOCK_HOUR }} h</span>
                                @endif
                            </td>
                            <td class="no-label">
                                <div class="acts">
                                    @if ($remind && in_array($group, ['retard', 'bientot', 'bloques', 'essai'], true))
                                        <a href="{{ $remind }}" target="_blank" rel="noopener" class="btn btn-ghost" title="Message de rappel prêt à envoyer au patron">💬 Relancer</a>
                                    @endif
                                    <a href="{{ route('admin.restaurants.subscription', $r->id) }}#paiement" class="btn btn-primary">💰 Paiement</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
