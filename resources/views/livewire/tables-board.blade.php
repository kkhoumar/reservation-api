<div>
    <x-flash />

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
        <h1 style="color: var(--color-primary); margin: 0;">Plan des tables</h1>
        @if ($canManage && ($zones->flatMap->tables->isNotEmpty() || $loose->isNotEmpty()))
            <a href="{{ route('tables.manage') }}" class="btn btn-ghost" style="text-decoration: none;"><x-icon name="pencil" size="16" /> Mes tables</a>
        @endif
    </div>

    @php
        $groups = $zones->filter(fn ($z) => $z->tables->isNotEmpty())->map(fn ($z) => ['name' => $z->name, 'tables' => $z->tables]);
        if ($loose->isNotEmpty()) $groups->push(['name' => $groups->isEmpty() ? '' : 'Autres tables', 'tables' => $loose]);
    @endphp

    @if ($groups->isEmpty())
        <div class="card" style="margin-top: 18px; text-align: center; padding: 28px 20px;">
            <div style="width: 52px; height: 52px; border-radius: 16px; background: var(--color-mint); color: var(--color-primary); display: grid; place-items: center; margin: 0 auto 12px;"><x-icon name="utensils" size="24" /></div>
            <h3 style="margin: 0 0 6px;">Aucune table pour l'instant</h3>
            @if ($canManage)
                <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0 0 16px;">Créez vos tables en une minute : les serveuses les verront ici pour prendre les commandes.</p>
                <a href="{{ route('tables.manage') }}" class="btn btn-primary" style="text-decoration: none;">Créer mes tables</a>
            @else
                <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0;">Le patron n'a pas encore créé les tables.@if ($quickSale) En attendant, utilisez la vente rapide ci-dessous.@endif</p>
            @endif
        </div>
    @endif

    @foreach ($groups as $group)
        @if ($group['name'] !== '')
            <h3 style="margin-top: 24px; color: var(--color-text-secondary);">{{ $group['name'] }}</h3>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-top: 16px;">
            @foreach ($group['tables'] as $table)
                <a href="{{ route('orders.new', ['table' => $table->id]) }}"
                   style="text-decoration: none;">
                    <div class="card" style="text-align: center; border-left: 6px solid {{ $table->statusColor() }};">
                        <div style="font-weight: 700; font-size: 18px; color: var(--color-text);">
                            {{ $table->label }}
                        </div>
                        <div style="font-size: 13px; color: var(--color-text-secondary); margin-top: 4px;">
                            {{ match($table->status) {
                                'free' => 'Libre',
                                'occupied' => 'Occupée',
                                'awaiting_payment' => 'À payer',
                                default => $table->status,
                            } }}
                        </div>
                        <div style="font-size: 12px; color: var(--color-text-secondary);">
                            {{ $table->capacity }} places
                        </div>
                        @if ($atCounter[$table->id] ?? 0)
                            <div style="margin-top: 6px; font-size: 12px; font-weight: 600; color: var(--color-accent);">
                                {{ $atCounter[$table->id] }} bon{{ $atCounter[$table->id] > 1 ? 's' : '' }} au comptoir
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endforeach

    @if ($quickSale)
        <div style="margin-top: 24px;">
            <a href="{{ route('orders.new') }}" class="btn btn-primary">+ Vente rapide / à emporter</a>
        </div>
    @endif
</div>
