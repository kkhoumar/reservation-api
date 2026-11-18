<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Crédit client
            </div>
            <h1>Crédits clients</h1>
            <p style="color: var(--color-text-secondary); font-size: 14px; margin: 6px 0 0 0;">
                Total dû au maquis : <strong style="color: var(--color-accent);">{{ number_format($totalDue, 0, ',', ' ') }} FCFA</strong>
            </p>
        </div>
        @if ($canManage)
            <button wire:click="$toggle('showCreate')" class="btn btn-primary">+ Nouveau client</button>
        @endif
    </div>

    @if ($showCreate && $canManage)
        <div class="card" style="margin-bottom: 16px; max-width: 640px;">
            <h3 style="margin-top: 0;">Nouveau client</h3>
            <form wire:submit="createCustomer" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Nom</label>
                    <input type="text" wire:model="name" placeholder="M. Konan" style="width: 100%; margin-top: 4px;">
                    @error('name') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Téléphone (pour la relance)</label>
                    <input type="tel" wire:model="phone" placeholder="07 07 07 07 07" style="width: 100%; margin-top: 4px;">
                    @error('phone') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Plafond (FCFA, facultatif)</label>
                    <input type="number" wire:model="creditLimit" placeholder="Ex : 20000" style="width: 100%; margin-top: 4px;">
                    @error('creditLimit') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">Ajouter</button>
                </div>
            </form>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: start;">
        <div class="card">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un client (nom ou numéro)" style="width: 100%; margin-bottom: 12px;">

            @if ($customers->isEmpty())
                <p style="color: var(--color-text-secondary); font-size: 14px;">Aucun client{{ $search !== '' ? ' trouvé' : ' pour le moment' }}.</p>
            @else
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="text-align: left; color: var(--color-text-secondary); font-size: 13px;">
                            <th style="padding: 8px 0; font-weight: 600;">Client</th>
                            <th style="padding: 8px 0; font-weight: 600; text-align: right;">Doit</th>
                            <th style="padding: 8px 0; font-weight: 600; text-align: right;">Plafond</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr style="border-top: 1px solid var(--color-border); {{ $selected?->id === $customer->id ? 'background: var(--color-bg);' : '' }}">
                                <td style="padding: 10px 0;">
                                    <strong>{{ $customer->name }}</strong>
                                    @if ($customer->phone)
                                        <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">{{ $customer->phone }}</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 0; text-align: right; font-weight: 700; color: {{ $customer->current_balance > 0 ? 'var(--color-accent)' : 'var(--color-primary-300)' }};">
                                    {{ number_format($customer->current_balance, 0, ',', ' ') }} FCFA
                                </td>
                                <td style="padding: 10px 0; text-align: right; color: var(--color-text-secondary);">
                                    {{ $customer->credit_limit !== null ? number_format($customer->credit_limit, 0, ',', ' ') : '—' }}
                                </td>
                                <td style="padding: 10px 0 10px 12px; text-align: right;">
                                    <button wire:click="select({{ $customer->id }})" class="btn btn-ghost" style="padding: 6px 12px; font-size: 13px;">Ouvrir</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="card">
            @if ($selected)
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <h3 style="margin: 0;">{{ $selected->name }}</h3>
                    <button wire:click="closeCustomer" style="background: none; border: none; cursor: pointer; color: var(--color-text-secondary); font-size: 18px;">✕</button>
                </div>
                <div style="font-size: 26px; font-weight: 800; margin: 8px 0 4px 0; color: {{ $selectedBalance > 0 ? 'var(--color-accent)' : 'var(--color-primary-300)' }};">
                    {{ number_format($selectedBalance, 0, ',', ' ') }} FCFA
                </div>
                <div style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 14px;">à rembourser</div>

                @if ($selectedBalance > 0 && $whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-ghost" style="width: 100%; justify-content: center; text-decoration: none; margin-bottom: 14px;">
                        Relancer sur WhatsApp
                    </a>
                @endif

                @if ($selectedBalance > 0)
                    <div style="border-top: 1px dashed var(--color-border); padding-top: 12px;">
                        <label style="font-size: 13px; font-weight: 600;">Remboursement reçu (FCFA)</label>
                        <input type="number" wire:model="repaymentAmount" placeholder="{{ $selectedBalance }}" style="width: 100%; margin: 4px 0 8px 0; font-size: 18px;">
                        @error('repaymentAmount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror
                        <input type="text" wire:model="repaymentNote" maxlength="255" placeholder="Note (facultatif)" style="width: 100%; margin-bottom: 10px;">
                        <button wire:click="recordRepayment" class="btn btn-primary" style="width: 100%; justify-content: center;">Encaisser le remboursement</button>
                    </div>
                @endif

                @if ($canManage)
                    <div style="border-top: 1px dashed var(--color-border); padding-top: 12px; margin-top: 14px;">
                        <label style="font-size: 13px; font-weight: 600;">Plafond (vide = sans plafond)</label>
                        <div style="display: flex; gap: 8px; margin-top: 4px;">
                            <input type="number" wire:model="editLimit" style="flex: 1; min-width: 0;">
                            <button wire:click="saveLimit" class="btn btn-ghost">OK</button>
                        </div>
                        @error('editLimit') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                    </div>
                @endif

                <h3 style="margin: 18px 0 6px 0;">Historique</h3>
                @forelse ($history as $t)
                    <div style="padding: 8px 0; border-top: 1px solid var(--color-border); font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; gap: 8px;">
                            <span>
                                <strong>{{ $t->created_at->format('d/m/Y H:i') }}</strong> ·
                                {{ $t->type === 'charge' ? 'Consommation' : 'Remboursement' }}
                            </span>
                            <strong style="color: {{ $t->type === 'charge' ? 'var(--color-accent)' : 'var(--color-primary-300)' }}; white-space: nowrap;">
                                {{ $t->type === 'charge' ? '+' : '−' }}{{ number_format($t->amount, 0, ',', ' ') }}
                            </strong>
                        </div>

                        @if ($t->type === 'charge' && $t->order)
                            <div style="margin: 4px 0 2px 10px; padding-left: 8px; border-left: 2px solid var(--color-border);">
                                @foreach ($t->order->items as $item)
                                    <div style="display: flex; justify-content: space-between; gap: 8px;">
                                        <span>{{ $item->quantity }} × {{ $item->product?->name ?? 'Produit supprimé' }}</span>
                                        <span style="color: var(--color-text-secondary);">{{ number_format($item->total, 0, ',', ' ') }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div style="font-size: 12px; color: var(--color-text-secondary);">
                                {{ $t->order->order_number }}
                                @if ($t->order->restaurantTable) · {{ $t->order->restaurantTable->label }} @endif
                                @if ($t->order->server) · servi par {{ $t->order->server->first_name }} @endif
                                · crédit autorisé par {{ $t->recordedBy?->first_name ?? '—' }}
                            </div>
                        @else
                            <div style="font-size: 12px; color: var(--color-text-secondary);">
                                Encaissé par {{ $t->recordedBy?->first_name ?? '—' }}@if ($t->note) · {{ $t->note }}@endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p style="font-size: 13px; color: var(--color-text-secondary);">Aucun mouvement.</p>
                @endforelse
            @else
                <h3 style="margin-top: 0;">Comment ça marche</h3>
                <ol style="font-size: 13px; color: var(--color-text-secondary); padding-left: 18px; line-height: 1.6; margin: 0;">
                    <li>Au comptoir, le patron ou la gérante met un bon <strong>« À crédit »</strong> d'un client.</li>
                    <li>La dette s'affiche ici, avec qui l'a autorisée.</li>
                    <li>Quand le client paie, « Encaisser le remboursement » : l'argent entre en caisse.</li>
                </ol>
            @endif
        </div>
    </div>
</div>
