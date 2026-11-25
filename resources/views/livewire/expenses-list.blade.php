<div>
    <x-flash />

    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Suivi financier
        </div>
        <h1>Dépenses</h1>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 16px; align-items: start;">

        @if (\App\Support\ReadOnlyGuard::isReadOnly())
        <div class="card" style="color: var(--color-text-secondary); font-size: 14px;">
            <h3 style="margin-top: 0; color: var(--color-text);">Lecture seule</h3>
            Les nouvelles dépenses ne peuvent pas être enregistrées tant que l'abonnement n'est pas réglé. L'historique reste consultable.
        </div>
        @else
        <div class="card">
            <h3 style="margin-top: 0;">Nouvelle dépense</h3>

            <label style="font-size: 13px; font-weight: 600;">Catégorie</label>
            <select wire:model="category" style="width: 100%; margin: 6px 0 14px 0;">
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <label style="font-size: 13px; font-weight: 600;">Montant (FCFA)</label>
            <x-money-input model="amount" style="width: 100%; margin: 6px 0 14px 0;" />
            @error('amount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 10px;">{{ $message }}</div> @enderror

            <label style="font-size: 13px; font-weight: 600;">Description <span style="font-weight: 500; color: var(--color-text-secondary);">facultatif</span></label>
            <input type="text" wire:model="description" placeholder="Ex : Achat tomates marché" style="width: 100%; margin: 6px 0 16px 0;">

            @include('partials.photo-capture', ['model' => 'receiptPhoto', 'label' => 'Photo du reçu', 'hint' => 'Obligatoire. Sans reçu, photographiez ce qui a été acheté.'])

            <button wire:click="addExpense" class="btn btn-primary" style="width: 100%;">
                Enregistrer la dépense
            </button>
        </div>
        @endif

        <div class="card">
            @if ($canSeeHistory)
                {{-- Filtres de l'historique --}}
                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                    @foreach ($periods as $value => $label)
                        <button wire:click="$set('period', '{{ $value }}')" class="btn {{ $period === $value ? 'btn-secondary' : 'btn-ghost' }}" style="padding: 6px 12px; font-size: 13px;">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; align-items: flex-end;">
                    @if ($period === 'custom')
                        <div>
                            <label style="font-size: 12px; font-weight: 600; display: block;">Du</label>
                            <input type="date" wire:model.live="from">
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; display: block;">Au</label>
                            <input type="date" wire:model.live="to">
                        </div>
                    @endif
                    <div>
                        <label style="font-size: 12px; font-weight: 600; display: block;">Catégorie</label>
                        <select wire:model.live="filterCategory">
                            <option value="">Toutes</option>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                <h3 style="margin: 0;">{{ $rangeLabel }} <span style="font-weight: 500; color: var(--color-text-secondary); font-size: 13px;">· {{ $count }} dépense{{ $count > 1 ? 's' : '' }}</span></h3>
                <div style="font-size: 20px; font-weight: 800; color: {{ $total > 0 ? 'var(--color-danger)' : 'var(--color-text-secondary)' }};">
                    {{ $total > 0 ? '−' : '' }}{{ number_format($total, 0, ',', ' ') }} FCFA
                </div>
            </div>

            @if ($byCategory->count() > 1)
                <div style="background: var(--color-bg); border-radius: 10px; padding: 10px 12px; margin-bottom: 14px;">
                    @foreach ($byCategory as $row)
                        <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 3px 0;">
                            <span>{{ $categories[$row->category] ?? $row->category }} <span style="color: var(--color-text-secondary);">({{ $row->n }})</span></span>
                            <strong>{{ number_format($row->total, 0, ',', ' ') }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($expenses->isEmpty())
                <p style="color: var(--color-text-secondary); font-size: 13px;">Aucune dépense sur cette période.</p>
            @else
                @php $currentDay = null; @endphp
                @foreach ($expenses as $expense)
                    @php $day = $expense->expense_date->format('Y-m-d'); @endphp
                    @if ($day !== $currentDay)
                        @php $currentDay = $day; @endphp
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--color-text-secondary); margin: 14px 0 2px 0;">
                            {{ ucfirst($expense->expense_date->locale('fr')->isoFormat('dddd D MMMM YYYY')) }}
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
                        <div>
                            <div style="font-weight: 600; font-size: 14px;">{{ $expense->description ?: ($categories[$expense->category] ?? $expense->category) }}</div>
                            <div style="font-size: 12px; color: var(--color-text-secondary);">
                                {{ $categories[$expense->category] ?? $expense->category }}
                                · {{ $expense->expense_date->format('H:i') }}
                                · par {{ $expense->user?->first_name ?? '—' }}
                                @if (! $expense->cash_register_id) · <em>hors caisse</em> @endif
                            </div>
                            @if ($expense->receipt_path)
                                <a href="{{ route('expenses.receipt', $expense) }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12.5px; font-weight: 700; color: var(--color-accent); text-decoration: none;">
                                    <img src="{{ route('expenses.receipt', $expense) }}" alt="" loading="lazy" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid var(--color-border);"> Voir le reçu
                                </a>
                            @else
                                <div style="font-size: 12px; color: #9A5B0C; margin-top: 4px;">Sans reçu</div>
                            @endif
                        </div>
                        <div style="color: var(--color-danger); font-weight: 700; white-space: nowrap;">
                            −{{ number_format($expense->amount, 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                @endforeach

                <div style="margin-top: 12px;">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
