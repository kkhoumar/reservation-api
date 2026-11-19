@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $small = 'font-size: 12px; color: var(--color-text-secondary);';
@endphp

<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">{{ session('success') }}</div>
    @endif

    <div style="margin-bottom: 16px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">Boissons et produits</div>
        <h1>Stock</h1>
        <p style="font-size: 14px; color: var(--color-text-secondary); margin: 6px 0 0 0;">
            Le stock ne bouge que par une vente, une livraison, une perte ou un inventaire, et chaque mouvement est tracé.
        </p>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px;">
        @foreach ($tabs as $key => $label)
            @continue(\App\Support\ReadOnlyGuard::isReadOnly() && in_array($key, ['delivery', 'loss'], true))
            <button wire:click="setTab('{{ $key }}')" class="btn {{ $tab === $key ? 'btn-secondary' : 'btn-ghost' }}" style="padding: 8px 14px; font-size: 13px;">{{ $label }}</button>
        @endforeach
        <a href="{{ route('stock.count') }}" class="btn btn-primary" style="padding: 8px 14px; font-size: 13px; margin-left: auto;"><x-icon name="clipboard" size="15" /> Compter le stock</a>
    </div>

    {{-- ================= VUE D'ENSEMBLE ================= --}}
    @if ($tab === 'overview')
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 16px;">
            <div class="card" style="padding: 14px 16px;">
                <div style="{{ $small }}">Valeur du stock</div>
                <div style="font-size: 22px; font-weight: 800;">{{ $fcfa($stockValue) }} FCFA</div>
                <div style="{{ $small }}">au prix d'achat (ou de vente s'il manque)</div>
            </div>
            <div class="card" style="padding: 14px 16px; border-left: 4px solid {{ $products->filter->isLowStock()->isNotEmpty() ? 'var(--color-danger)' : 'var(--color-border)' }};">
                <div style="{{ $small }}">Bientôt en rupture</div>
                <div style="font-size: 22px; font-weight: 800;">{{ $products->filter->isLowStock()->count() }}</div>
            </div>
            @if ($missingCost > 0)
                <div class="card" style="padding: 14px 16px; border-left: 4px solid var(--color-accent);">
                    <div style="{{ $small }}">Prix d'achat manquant</div>
                    <div style="font-size: 22px; font-weight: 800;">{{ $missingCost }}</div>
                    <div style="{{ $small }}">à renseigner pour un bénéfice juste</div>
                </div>
            @endif
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid var(--color-border);">
                        <th style="padding: 10px 16px; {{ $small }} font-weight: 600;">Produit</th>
                        <th style="padding: 10px 16px; {{ $small }} font-weight: 600; text-align: right;">En stock</th>
                        <th style="padding: 10px 16px; {{ $small }} font-weight: 600; text-align: right;">Prix d'achat</th>
                        <th style="padding: 10px 16px; {{ $small }} font-weight: 600; text-align: right;">Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $p)
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 10px 16px;"><strong>{{ $p->name }}</strong> <span style="{{ $small }}">{{ $p->category?->name }}</span></td>
                            <td style="padding: 10px 16px; text-align: right; font-weight: 700; color: {{ $p->isLowStock() ? 'var(--color-danger)' : 'inherit' }};">
                                {{ $p->stock_quantity }} @if ($p->isLowStock()) <x-icon name="alert" size="14" style="color: var(--color-warning);" /> @endif
                            </td>
                            <td style="padding: 10px 16px; text-align: right;">{{ $p->cost_price !== null ? $fcfa($p->cost_price) : '—' }}</td>
                            <td style="padding: 10px 16px; text-align: right;">{{ $fcfa(max(0, $p->stock_quantity) * $p->unitValue()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($recentCounts->isNotEmpty())
            <div class="card" style="margin-top: 16px;">
                <h3 style="margin-top: 0;">Derniers comptages</h3>
                @foreach ($recentCounts as $c)
                    <a href="{{ route('stock.count.show', $c) }}" style="display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-top: 1px solid var(--color-border); font-size: 14px; color: inherit; text-decoration: none;">
                        <span>{{ $c->created_at->format('d/m/Y H:i') }} · {{ $c->products_counted }} {{ $c->products_counted > 1 ? 'produits comptés' : 'produit compté' }} · par {{ $c->user?->first_name }}</span>
                        <strong style="color: {{ $c->value_gap < 0 ? 'var(--color-danger)' : ($c->value_gap > 0 ? 'var(--color-primary-300)' : 'inherit') }};">
                            {{ $c->value_gap === 0 ? 'juste' : ($c->value_gap < 0 ? 'manque ' . $fcfa(abs($c->value_gap)) : 'en trop ' . $fcfa($c->value_gap)) . ' F' }}
                        </strong>
                    </a>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ================= LIVRAISON ================= --}}
    @if ($tab === 'delivery')
        <div class="card">
            <h3 style="margin-top: 0;">Enregistrer une livraison</h3>
            <p style="{{ $small }} margin-top: 0;">Saisissez seulement les produits livrés. Le prix d'achat devient le nouveau prix d'achat du produit.</p>

            <label style="font-size: 13px; font-weight: 600;">Fournisseur (facultatif)</label>
            <input type="text" wire:model="supplier" maxlength="120" placeholder="Ex : Solibra, dépôt du quartier" style="width: 100%; max-width: 360px; display: block; margin: 4px 0 14px 0;">

            @foreach ($categories as $category)
                @php $catProducts = $products->where('category_id', $category->id); @endphp
                @if ($catProducts->isNotEmpty())
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--color-text-secondary); margin: 12px 0 4px 0;">{{ $category->name }}</div>
                    @foreach ($catProducts as $p)
                        <div style="display: grid; grid-template-columns: 1fr 100px 130px; gap: 8px; align-items: center; padding: 5px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;">
                            <span>{{ $p->name }} <span style="{{ $small }}">· stock {{ $p->stock_quantity }}</span></span>
                            <input type="number" min="0" wire:model="deliveryLines.{{ $p->id }}.quantity" placeholder="Qté" style="padding: 6px 8px;">
                            <x-money-input model="deliveryLines.{{ $p->id }}.unit_cost" placeholder="Prix d'achat" style="padding: 6px 8px;" />
                        </div>
                    @endforeach
                @endif
            @endforeach
            @error('deliveryLines.*') <div style="color: var(--color-danger); font-size: 13px; margin-top: 8px;">{{ $message }}</div> @enderror

            <label style="display: flex; gap: 10px; align-items: flex-start; margin: 16px 0; cursor: pointer; font-size: 14px;">
                <input type="checkbox" wire:model="paidFromRegister" style="margin-top: 3px;">
                <span><strong>Payée avec l'argent de la caisse</strong>
                    <span style="display: block; {{ $small }}">Crée automatiquement la dépense « Achat de marchandises » dans la caisse ouverte.</span>
                </span>
            </label>
            @error('delivery') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 10px;">{{ $message }}</div> @enderror

            <button wire:click="saveDelivery" class="btn btn-primary">Enregistrer la livraison</button>
        </div>
    @endif

    {{-- ================= PERTE ================= --}}
    @if ($tab === 'loss')
        <div class="card" style="max-width: 480px;">
            <h3 style="margin-top: 0;">Perte ou casse</h3>
            <p style="{{ $small }} margin-top: 0;">Bouteille cassée, produit périmé ou renversé… La perte apparaît dans le tableau de bord.</p>

            <label style="font-size: 13px; font-weight: 600;">Produit</label>
            <select wire:model="lossProductId" style="width: 100%; margin: 4px 0 12px 0;">
                <option value="">— Choisir —</option>
                @foreach ($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} (stock {{ $p->stock_quantity }})</option>
                @endforeach
            </select>
            @error('lossProductId') <div style="color: var(--color-danger); font-size: 13px; margin: -6px 0 8px 0;">{{ $message }}</div> @enderror

            <label style="font-size: 13px; font-weight: 600;">Quantité perdue</label>
            <input type="number" min="1" wire:model="lossQuantity" style="width: 100%; margin: 4px 0 12px 0;">
            @error('lossQuantity') <div style="color: var(--color-danger); font-size: 13px; margin: -6px 0 8px 0;">{{ $message }}</div> @enderror

            <label style="font-size: 13px; font-weight: 600;">Motif</label>
            <input type="text" wire:model="lossReason" maxlength="255" placeholder="Ex : casier tombé, bière périmée" style="width: 100%; margin: 4px 0 4px 0;">
            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;">
                @foreach (['Casse', 'Périmé', 'Renversé', 'Offert au personnel'] as $r)
                    <button type="button" wire:click="$set('lossReason', @js($r))" style="background: white; border: 1px solid var(--color-border); border-radius: 999px; padding: 3px 10px; font-size: 12px; cursor: pointer;">{{ $r }}</button>
                @endforeach
            </div>
            @error('lossReason') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror

            <button wire:click="saveLoss" class="btn btn-danger">Enregistrer la perte</button>
        </div>
    @endif

    {{-- ================= HISTORIQUE ================= --}}
    @if ($tab === 'history')
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
                <h3 style="margin: 0;">Tous les mouvements</h3>
                <select wire:model.live="historyType">
                    <option value="">Tous</option>
                    @foreach ($movementTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <p style="{{ $small }} margin-top: 0;">Les ventes ne sont pas listées ici : elles sont dans « Ventes du jour » et dans la caisse.</p>

            @forelse ($movements as $m)
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                    <span>
                        <strong>{{ $m->created_at->format('d/m/Y H:i') }}</strong> · {{ $m->typeLabel() }} · {{ $m->product?->name }}
                        <span style="display: block; {{ $small }}">
                            {{ $m->stock_before }} → {{ $m->stock_after }}
                            @if ($m->supplier) · {{ $m->supplier }} @endif
                            @if ($m->unit_cost !== null) · {{ $fcfa($m->unit_cost) }} F l'unité @endif
                            @if ($m->reason) · {{ $m->reason }} @endif
                            · par {{ $m->user?->first_name ?? '—' }}
                        </span>
                    </span>
                    <strong style="white-space: nowrap; color: {{ $m->quantity < 0 ? 'var(--color-danger)' : 'var(--color-primary-300)' }};">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</strong>
                </div>
            @empty
                <p style="{{ $small }}">Aucun mouvement.</p>
            @endforelse

            @if ($movements && $movements->hasPages())
                <div style="margin-top: 12px;">{{ $movements->links() }}</div>
            @endif
        </div>
    @endif
</div>
