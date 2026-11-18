<div wire:poll.5s>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px;">
            {{ session('error') }}
        </div>
    @endif

    <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Paiement avant service
        </div>
        <h1>Comptoir</h1>
        <p style="color: var(--color-text-secondary); font-size: 14px; margin: 6px 0 0 0;">
            Encaissez l'argent apporté par la serveuse, puis servez. Pas d'argent, pas de boisson.
        </p>
    </div>

    @if (! $registerOpen)
        <div class="card" style="border-left: 6px solid var(--color-accent); margin-bottom: 16px; font-size: 14px;">
            La caisse n'est pas ouverte. <a href="{{ route('cash-register.manage') }}" style="color: var(--color-accent); font-weight: 600;">Ouvrez-la</a> pour que les encaissements soient comptés dans la session.
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: start;">
        <div>
            <h3 style="margin: 0 0 10px 0;">
                À encaisser
                <span style="display: inline-block; min-width: 24px; padding: 1px 8px; margin-left: 6px; border-radius: 999px; background: {{ $pending->isEmpty() ? 'var(--color-border)' : 'var(--color-accent)' }}; color: {{ $pending->isEmpty() ? 'var(--color-text-secondary)' : 'white' }}; font-size: 13px; text-align: center;">{{ $pending->count() }}</span>
            </h3>

            @forelse ($pending as $order)
                <div class="card" style="margin-bottom: 12px; border-left: 6px solid {{ $selected?->id === $order->id ? 'var(--color-primary)' : 'var(--color-accent)' }};">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <strong style="font-size: 16px;">{{ $order->restaurantTable?->label ?? 'À emporter' }}</strong>
                            <span style="color: var(--color-text-secondary); font-size: 13px;">
                                · {{ $order->server?->first_name ?? '—' }} · {{ $order->order_number }} · {{ $order->updated_at->diffForHumans(short: true) }}
                            </span>
                        </div>
                        <strong style="font-size: 20px; color: var(--color-primary);">{{ number_format($order->total, 0, ',', ' ') }} FCFA</strong>
                    </div>

                    <div style="margin: 8px 0 12px 0; font-size: 14px;">
                        @foreach ($order->items as $item)
                            <span style="display: inline-block; margin: 0 12px 4px 0;">{{ $item->quantity }} × {{ $item->product->name }}@if ($item->note) <span style="color: var(--color-text-secondary);">({{ $item->note }})</span>@endif</span>
                        @endforeach
                    </div>

                    @if ($selected?->id === $order->id)
                        <div style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; padding-top: 12px; border-top: 1px dashed var(--color-border);">
                            <div>
                                <label style="font-size: 13px; font-weight: 600; display: block;">Montant reçu (FCFA)</label>
                                <input type="number" wire:model.live.debounce.300ms="amountReceived" style="font-size: 18px; width: 170px; margin-top: 4px;" autofocus>
                            </div>
                            <div style="font-size: 14px; padding-bottom: 10px;">
                                Monnaie à rendre :
                                <strong style="color: var(--color-accent);">{{ number_format(max(0, (int) $amountReceived - $order->total), 0, ',', ' ') }} FCFA</strong>
                            </div>
                        </div>
                        @error('amountReceived') <div style="color: var(--color-danger); font-size: 13px; margin-top: 8px;">{{ $message }}</div> @enderror
                        <div style="display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                            <button wire:click="confirmPayment" class="btn btn-primary">Encaissé, servir</button>
                            <button wire:click="cancelPayment" class="btn btn-ghost">Retour</button>
                        </div>
                    @elseif ($creditOrderId === $order->id)
                        <div style="padding-top: 12px; border-top: 1px dashed var(--color-border);">
                            <label style="font-size: 13px; font-weight: 600; display: block;">Sur l'ardoise de :</label>
                            @if ($customers->isEmpty())
                                <p style="font-size: 13px; color: var(--color-text-secondary);">
                                    Aucun client. <a href="{{ route('ardoise') }}" style="color: var(--color-accent); font-weight: 600;">Ajoutez-le dans Ardoise</a>.
                                </p>
                            @else
                                <select wire:model="creditCustomerId" style="min-width: 240px; margin-top: 4px;">
                                    <option value="">— Choisir le client —</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('creditCustomerId') <div style="color: var(--color-danger); font-size: 13px; margin-top: 8px;">{{ $message }}</div> @enderror
                            <div style="display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                                <button wire:click="confirmCredit" class="btn btn-primary">Mettre sur l'ardoise, servir</button>
                                <button wire:click="cancelCredit" class="btn btn-ghost">Retour</button>
                            </div>
                        </div>
                    @else
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button wire:click="startPayment({{ $order->id }})" class="btn btn-secondary">Encaisser</button>
                            @if ($canCredit)
                                <button wire:click="startCredit({{ $order->id }})" class="btn btn-ghost">Sur l'ardoise</button>
                            @endif
                            <button wire:click="cancelOrder({{ $order->id }})" wire:confirm="Annuler ce bon ? Les articles seront remis en stock." class="btn btn-ghost" style="color: var(--color-danger);">Annuler le bon</button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="card" style="color: var(--color-text-secondary); font-size: 14px;">
                    Aucun bon en attente. Les bons envoyés par les serveuses apparaissent ici automatiquement.
                </div>
            @endforelse
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div class="card">
                <h3 style="margin-top: 0;">Apporté aujourd'hui</h3>
                @forelse ($byServer as $row)
                    <div style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                        <span>{{ $row->server?->first_name ?? '—' }} <span style="color: var(--color-text-secondary); font-size: 12px;">· {{ $row->bons }} bon{{ $row->bons > 1 ? 's' : '' }}</span></span>
                        <strong>{{ number_format($row->amount, 0, ',', ' ') }}</strong>
                    </div>
                @empty
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0;">Rien d'encaissé aujourd'hui.</p>
                @endforelse
            </div>

            @if ($tablesToRelease->isNotEmpty())
                <div class="card">
                    <h3 style="margin-top: 0;">Tables à libérer</h3>
                    <p style="font-size: 12px; color: var(--color-text-secondary); margin-top: 0;">Tout est réglé. Libérez la table quand les clients partent.</p>
                    @foreach ($tablesToRelease as $table)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-top: 1px solid var(--color-border);">
                            <span style="font-size: 14px;">{{ $table->label }}</span>
                            <button wire:click="releaseTable({{ $table->id }})" class="btn btn-ghost" style="padding: 5px 12px; font-size: 13px;">Libérer</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
