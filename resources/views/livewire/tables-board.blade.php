<div>
    <x-flash />

    <h1 style="color: var(--color-primary);">Plan des tables</h1>

    @foreach ($zones as $zone)
        <h3 style="margin-top: 24px; color: var(--color-text-secondary);">{{ $zone->name }}</h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
            @foreach ($zone->tables as $table)
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
