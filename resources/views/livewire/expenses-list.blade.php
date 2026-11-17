<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Suivi financier
        </div>
        <h1>Dépenses du jour</h1>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 16px;">

        <div class="card">
            <h3 style="margin-top: 0;">Nouvelle dépense</h3>

            <label style="font-size: 13px; font-weight: 600;">Catégorie</label>
            <select wire:model="category" style="width: 100%; margin: 6px 0 14px 0;">
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <label style="font-size: 13px; font-weight: 600;">Montant (FCFA)</label>
            <input type="number" wire:model="amount" style="width: 100%; margin: 6px 0 14px 0;">
            @error('amount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 10px;">{{ $message }}</div> @enderror

            <label style="font-size: 13px; font-weight: 600;">Description (optionnel)</label>
            <input type="text" wire:model="description" placeholder="Ex : Achat tomates marché" style="width: 100%; margin: 6px 0 16px 0;">

            <button wire:click="addExpense" class="btn btn-primary" style="width: 100%;">
                Enregistrer la dépense
            </button>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0;">Aujourd'hui</h3>
                <div style="font-size: 18px; font-weight: 800; color: var(--color-danger);">
                    -{{ number_format($total, 0, ',', ' ') }} FCFA
                </div>
            </div>

            @if ($expenses->isEmpty())
                <p style="color: var(--color-text-secondary); font-size: 13px;">Aucune dépense enregistrée aujourd'hui.</p>
            @else
                @foreach ($expenses as $expense)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
                        <div>
                            <div style="font-weight: 600; font-size: 14px;">{{ $expense->description ?: $categories[$expense->category] ?? $expense->category }}</div>
                            <div style="font-size: 12px; color: var(--color-text-secondary);">
                                {{ $categories[$expense->category] ?? $expense->category }} · {{ $expense->expense_date->format('H:i') }}
                            </div>
                        </div>
                        <div style="color: var(--color-danger); font-weight: 700;">
                            -{{ number_format($expense->amount, 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

    </div>
</div>