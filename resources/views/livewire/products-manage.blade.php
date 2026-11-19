<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="page-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Carte
            </div>
            <h1>Menu & produits</h1>
        </div>
        <div style="display: flex; gap: 10px;">
            <button wire:click="$set('showCategoryForm', true)" class="btn btn-ghost">+ Catégorie</button>
            <button wire:click="openCreateForm" class="btn btn-primary">+ Produit</button>
        </div>
    </div>

    @php $missingCostCount = $categories->flatMap->products->whereNull('cost_price')->count(); @endphp
    @if ($missingCostCount > 0)
        <div class="card" style="border-left: 6px solid var(--color-accent); margin-bottom: 16px; display: flex; gap: 10px; align-items: flex-start;">
            <x-icon name="alert" size="18" style="color: var(--color-accent); margin-top: 2px;" />
            <div><strong>{{ $missingCostCount }} {{ $missingCostCount > 1 ? 'produits n\'ont' : 'produit n\'a' }} pas de prix d'achat.</strong>
                <span style="display: block; font-size: 14px; color: var(--color-text-secondary); margin-top: 2px;">Touchez « Modifier » sur chaque produit marqué « prix d'achat manquant ». Sans ce prix, le bénéfice et la valeur du stock sont faux.</span></div>
        </div>
    @endif

    @if ($showCategoryForm)
        <div class="card" style="max-width: 400px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">Nouvelle catégorie</h3>
            <label style="font-size: 13px; font-weight: 600;">Nom</label>
            <input type="text" wire:model="newCategoryName" placeholder="Ex : Grillades" style="width: 100%; margin: 6px 0 14px 0;">
            @error('newCategoryName') <div style="color: var(--color-danger); font-size: 12px; margin-bottom: 10px;">{{ $message }}</div> @enderror
            <label style="font-size: 13px; font-weight: 600;">Famille</label>
            <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 2px;">Le comptage sépare les totaux des boissons et de la nourriture.</div>
            <div style="display: flex; gap: 8px; margin: 6px 0 16px 0;">
                @foreach (\App\Models\Category::KINDS as $kind => $kindLabel)
                    <button type="button" wire:click="$set('newCategoryKind', '{{ $kind }}')" class="btn {{ $newCategoryKind === $kind ? 'btn-secondary' : 'btn-ghost' }}" style="flex: 1; justify-content: center;">{{ $kindLabel }}</button>
                @endforeach
            </div>
            <div style="display: flex; gap: 10px;">
                <button wire:click="saveCategory" class="btn btn-primary" style="flex: 1;">Enregistrer</button>
                <button wire:click="$set('showCategoryForm', false)" class="btn btn-ghost" style="flex: 1;">Annuler</button>
            </div>
        </div>
    @endif

    @if ($showProductForm)
        <div class="card" style="max-width: 460px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">{{ $editingProductId ? 'Modifier' : 'Nouveau' }} produit</h3>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 600;">Nom</label>
                <input type="text" wire:model="name" style="width: 100%; margin-top: 6px;">
                @error('name') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 600;">Catégorie</label>
                <select wire:model="categoryId" style="width: 100%; margin-top: 6px;">
                    <option value="">-- Choisir --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('categoryId') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Prix de vente (FCFA)</label>
                    <x-money-input model="sellingPrice" :empty="''" style="width: 100%; margin-top: 6px;" />
                    @error('sellingPrice') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Prix d'achat (FCFA)</label>
                    <x-money-input model="costPrice" :empty="''" placeholder="Obligatoire" style="width: 100%; margin-top: 6px;" />
                    @error('costPrice') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
            </div>
            <div style="font-size: 12px; color: var(--color-text-secondary); margin: -8px 0 14px 0;">
                Les deux prix sont obligatoires. L'application s'en sert pour calculer le bénéfice et la valeur du stock. Chaque livraison met à jour le prix d'achat.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    @if ($editingProductId)
                        <label style="font-size: 13px; font-weight: 600;">Stock actuel</label>
                        <div style="margin-top: 6px; padding: 10px 12px; background: var(--color-bg); border-radius: 8px; font-size: 14px;">
                            {{ $stockQuantity }}
                            <a href="{{ route('stock.index') }}" style="display: block; font-size: 12px; color: var(--color-accent);">Livraison, perte ou inventaire →</a>
                        </div>
                    @else
                        <label style="font-size: 13px; font-weight: 600;">Stock de départ</label>
                        <input type="number" wire:model="stockQuantity" style="width: 100%; margin-top: 6px;">
                        @error('stockQuantity') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                    @endif
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Seuil d'alerte</label>
                    <input type="number" wire:model="stockAlertThreshold" style="width: 100%; margin-top: 6px;">
                </div>
            </div>

            <div style="margin-bottom: 16px; padding: 14px; border: 1px solid var(--color-border); border-radius: 14px;">
                <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer;">
                    <input type="checkbox" wire:model.live="isReturnable" style="margin-top: 2px;">
                    <span>
                        <strong style="font-size: 14px;">Bouteille consignée</strong>
                        <span style="display: block; font-size: 12.5px; color: var(--color-text-secondary); margin-top: 2px; line-height: 1.45;">
                            Bouteille en verre rendue au fournisseur : un casier vide contre un casier plein. Les canettes et le plastique ne sont pas consignés.
                        </span>
                    </span>
                </label>
                @if ($isReturnable)
                    <div style="margin-top: 12px;">
                        <label style="font-size: 13px; font-weight: 600;">Bouteilles par casier</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                            @foreach ([12, 15, 20, 24] as $size)
                                <button type="button" wire:click="$set('crateSize', '{{ $size }}')" class="btn {{ $crateSize === (string) $size ? 'btn-secondary' : 'btn-ghost' }}" style="padding: 6px 14px; font-size: 13px;">{{ $size }}</button>
                            @endforeach
                        </div>
                        <div style="font-size: 12.5px; color: var(--color-text-secondary); margin-top: 8px; line-height: 1.45;">
                            Au comptage, vous compterez aussi les bouteilles vides. L'application saura si une bière qui manque a été vendue sans bon ou est sortie du maquis.
                        </div>
                    </div>
                @endif
            </div>

            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" wire:model="isAvailable" id="isAvailable" style="width: auto;">
                <label for="isAvailable" style="font-size: 13px; font-weight: 600;">Disponible à la vente</label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button wire:click="saveProduct" class="btn btn-primary" style="flex: 1;">Enregistrer</button>
                <button wire:click="$set('showProductForm', false)" class="btn btn-ghost" style="flex: 1;">Annuler</button>
            </div>
        </div>
    @endif

    @foreach ($categories as $category)
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin: 24px 0 10px;">
            <h3 style="margin: 0; color: var(--color-text-secondary);">{{ $category->name }}</h3>
            <div style="display: inline-flex; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px; padding: 3px;" title="Famille de la catégorie, pour le comptage">
                @foreach (\App\Models\Category::KINDS as $kind => $kindLabel)
                    <button type="button" wire:click="setCategoryKind({{ $category->id }}, '{{ $kind }}')" style="border: 0; border-radius: 7px; padding: 5px 11px; font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; background: {{ $category->kind === $kind ? 'var(--color-primary)' : 'transparent' }}; color: {{ $category->kind === $kind ? '#fff' : 'var(--color-text-secondary)' }};">{{ $kindLabel }}</button>
                @endforeach
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 16px;">
            @if ($category->products->isEmpty())
                <p style="padding: 16px 20px; color: var(--color-text-secondary); font-size: 13px;">Aucun produit dans cette catégorie.</p>
            @else
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--color-border); text-align: left;">
                            <th style="padding: 10px 20px;">Produit</th>
                            <th style="padding: 10px 20px;">Prix</th>
                            <th style="padding: 10px 20px;">Stock</th>
                            <th style="padding: 10px 20px;">État</th>
                            <th style="padding: 10px 20px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($category->products as $product)
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: 12px 20px; font-weight: 600;">{{ $product->name }}</td>
                                <td style="padding: 12px 20px; color: var(--color-text-secondary);"><div>
                                    {{ number_format($product->selling_price, 0, ',', ' ') }} FCFA
                                    @if ($product->cost_price !== null)
                                        <span style="display: block; font-size: 12px;">achat {{ number_format($product->cost_price, 0, ',', ' ') }} · marge {{ number_format($product->unitMargin(), 0, ',', ' ') }}</span>
                                    @else
                                        <span style="display: block; font-size: 12px; color: var(--color-accent);">prix d'achat manquant</span>
                                    @endif
                                </div></td>
                                <td style="padding: 12px 20px;">
                                    @if ($product->isLowStock())
                                        <span style="color: var(--color-danger); font-weight: 600;"><x-icon name="alert" size="14" /> {{ $product->stock_quantity }}</span>
                                    @else
                                        <span style="color: var(--color-text-secondary);">{{ $product->stock_quantity }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 20px;">
                                    <span style="background: {{ $product->is_available ? 'var(--color-success-bg)' : 'var(--color-danger-bg)' }}; color: {{ $product->is_available ? 'var(--color-success)' : 'var(--color-danger)' }}; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                        {{ $product->is_available ? 'Disponible' : 'Indisponible' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 20px; text-align: right; white-space: nowrap;">
                                    <button wire:click="editProduct({{ $product->id }})" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px; margin-right: 6px;">Modifier</button>
                                    <button wire:click="toggleAvailability({{ $product->id }})" class="btn {{ $product->is_available ? 'btn-danger' : 'btn-primary' }}" style="padding: 6px 12px; font-size: 12px;">
                                        {{ $product->is_available ? 'Désactiver' : 'Activer' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</div>