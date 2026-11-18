<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
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

    @if ($showCategoryForm)
        <div class="card" style="max-width: 400px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">Nouvelle catégorie</h3>
            <label style="font-size: 13px; font-weight: 600;">Nom</label>
            <input type="text" wire:model="newCategoryName" placeholder="Ex : Grillades" style="width: 100%; margin: 6px 0 14px 0;">
            @error('newCategoryName') <div style="color: var(--color-danger); font-size: 12px; margin-bottom: 10px;">{{ $message }}</div> @enderror
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
                    <input type="number" wire:model="sellingPrice" style="width: 100%; margin-top: 6px;">
                    @error('sellingPrice') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Prix d'achat (FCFA)</label>
                    <input type="number" wire:model="costPrice" placeholder="Facultatif" style="width: 100%; margin-top: 6px;">
                    @error('costPrice') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
            </div>
            <div style="font-size: 12px; color: var(--color-text-secondary); margin: -8px 0 14px 0;">
                Le prix d'achat sert à calculer le bénéfice. Il est mis à jour à chaque livraison.
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
        <h3 style="margin-top: 24px; color: var(--color-text-secondary);">{{ $category->name }}</h3>

        <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 16px;">
            @if ($category->products->isEmpty())
                <p style="padding: 16px 20px; color: var(--color-text-secondary); font-size: 13px;">Aucun produit dans cette catégorie.</p>
            @else
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        @foreach ($category->products as $product)
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: 12px 20px; font-weight: 600;">{{ $product->name }}</td>
                                <td style="padding: 12px 20px; color: var(--color-text-secondary);">
                                    {{ number_format($product->selling_price, 0, ',', ' ') }} FCFA
                                    @if ($product->cost_price !== null)
                                        <span style="display: block; font-size: 12px;">achat {{ number_format($product->cost_price, 0, ',', ' ') }} · marge {{ number_format($product->unitMargin(), 0, ',', ' ') }}</span>
                                    @else
                                        <span style="display: block; font-size: 12px; color: var(--color-accent);">prix d'achat manquant</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 20px;">
                                    @if ($product->isLowStock())
                                        <span style="color: var(--color-danger); font-weight: 600;">⚠️ Stock : {{ $product->stock_quantity }}</span>
                                    @else
                                        <span style="color: var(--color-text-secondary);">Stock : {{ $product->stock_quantity }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 20px;">
                                    <span style="background: {{ $product->is_available ? 'rgba(94,191,160,0.15)' : 'rgba(217,59,59,0.1)' }}; color: {{ $product->is_available ? '#146652' : '#D93B3B' }}; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                        {{ $product->is_available ? 'Disponible' : 'Indisponible' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 20px; text-align: right;">
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