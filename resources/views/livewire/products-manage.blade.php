<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flash flash-error" role="alert">{{ session('error') }}</div>
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
                <select wire:model.live="categoryId" style="width: 100%; margin-top: 6px;">
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

            <div style="margin-bottom: 14px; padding: 14px; border: 1px solid var(--color-border); border-radius: 14px;">
                <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer;">
                    <input type="checkbox" wire:model.live="trackStock" style="margin-top: 2px;">
                    <span>
                        <strong style="font-size: 14px;">Suivre le stock</strong>
                        <span style="display: block; font-size: 12.5px; color: var(--color-text-secondary); margin-top: 2px; line-height: 1.45;">
                            Pour ce qu'on compte à l'unité : bouteilles, canettes, cigarettes… Laissez décoché pour un plat cuisiné : il ne sera jamais « épuisé » tout seul, et vous pourrez le marquer « Fini pour aujourd'hui ».
                        </span>
                    </span>
                </label>

                @if ($trackStock)
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px;">
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
                @endif
            </div>
            {{-- Bouteilles en verre rendues au dépôt : les vides montrent les bières vendues sans bon --}}
            @if ($trackStock)
            @php $crateOther = $crateSize !== '' && ! in_array($crateSize, ['12', '24'], true); @endphp
            <div class="rt" x-data="{ other: @js($crateOther) }" wire:key="rt-{{ $editingProductId ?? 'new' }}">
                <div class="rt-q">
                    <span class="rt-ico"><x-icon name="bottle" size="18" /></span>
                    <div>
                        <strong>Vous rendez les bouteilles vides au dépôt&nbsp;?</strong>
                        <span class="rt-hint">Exemple : Flag, Castel, Beaufort en verre. Pas pour les canettes, l'eau ou les plats.</span>
                    </div>
                </div>
                <div class="rt-yn" role="group" aria-label="Vous rendez les bouteilles vides au dépôt ?">
                    <button type="button" wire:click="setReturnable(true)" @click="other = false" class="{{ $isReturnable ? 'on' : '' }}" aria-pressed="{{ $isReturnable ? 'true' : 'false' }}">Oui</button>
                    <button type="button" wire:click="setReturnable(false)" class="{{ $isReturnable ? '' : 'on' }}" aria-pressed="{{ $isReturnable ? 'false' : 'true' }}">Non</button>
                </div>

                @if ($isReturnable)
                    <div class="rt-crate">
                        <label class="rt-label">Combien de bouteilles dans un casier&nbsp;?</label>
                        <div class="rt-chips">
                            @foreach (['12', '24'] as $size)
                                <button type="button" wire:click="$set('crateSize', '{{ $size }}')" @click="other = false"
                                        :class="{ on: !other && @js($crateSize === $size) }">{{ $size }}</button>
                            @endforeach
                            <button type="button" :class="{ on: other }"
                                    @click="other = true; if (['12','24'].includes($wire.crateSize)) $wire.set('crateSize', ''); $nextTick(() => $refs.crateInput?.focus())">Autre</button>
                            <span class="rt-other" x-show="other" x-cloak>
                                <input type="text" inputmode="numeric" maxlength="2" x-ref="crateInput" wire:model.blur="crateSize"
                                       placeholder="30" aria-label="Nombre de bouteilles dans un casier"
                                       @input="$event.target.value = $event.target.value.replace(/\D/g, '')">
                                <span>bouteilles</span>
                            </span>
                        </div>
                        @error('crateSize') <div class="rt-error">{{ $message }}</div> @enderror

                        <details class="rt-why">
                            <summary>Pourquoi c'est utile&nbsp;?</summary>
                            <p>Chaque bière bue ici laisse une bouteille vide. Le soir, vous comptez les vides.
                               S'il y a plus de vides que de bons, des bières ont peut-être été vendues sans bon&nbsp;: l'argent manque dans la caisse.</p>
                        </details>
                    </div>
                @endif
            </div>
            @endif

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
                                    @if (! $product->track_stock)
                                        <span style="color: var(--color-text-secondary); font-size: 13px; white-space: nowrap;">Non suivi</span>
                                    @elseif ($product->isLowStock())
                                        <span style="color: var(--color-danger); font-weight: 600;"><x-icon name="alert" size="14" /> {{ $product->stock_quantity }}</span>
                                    @else
                                        <span style="color: var(--color-text-secondary);">{{ $product->stock_quantity }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 20px;">
                                    @if ($product->is_available && $product->isSoldOutToday())
                                        <span style="background: var(--color-warning-bg, #FFF4E5); color: var(--color-accent-dark, #A84A1C); padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">Fini aujourd'hui</span>
                                    @else
                                    <span style="background: {{ $product->is_available ? 'var(--color-success-bg)' : 'var(--color-danger-bg)' }}; color: {{ $product->is_available ? 'var(--color-success)' : 'var(--color-danger)' }}; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                        {{ $product->is_available ? 'Disponible' : 'Indisponible' }}
                                    </span>
                                    @endif
                                </td>
                                <td class="pm-actions" style="padding: 12px 20px; text-align: right; white-space: nowrap;">
                                    @if (! $product->track_stock && $product->is_available)
                                        <button wire:click="toggleSoldOut({{ $product->id }})" class="btn pm-soldout {{ $product->isSoldOutToday() ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 12px; margin-right: 6px;">
                                            {{ $product->isSoldOutToday() ? 'Remettre en vente' : 'Fini pour aujourd\'hui' }}
                                        </button>
                                    @endif
                                    <button wire:click="editProduct({{ $product->id }})" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px; margin-right: 6px;">Modifier</button>
                                    <button wire:click="toggleAvailability({{ $product->id }})" class="btn {{ $product->is_available ? 'btn-ghost pm-off' : 'btn-primary' }}" style="padding: 6px 12px; font-size: 12px;">
                                        {{ $product->is_available ? 'Désactiver' : 'Activer' }}
                                    </button>
                                    <button wire:click="deleteProduct({{ $product->id }})"
                                            wire:confirm="Retirer « {{ $product->name }} » de la carte ?&#10;&#10;Vos anciennes ventes restent dans vos rapports. Vous pourrez le remettre sur la carte quand vous voulez."
                                            class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px; margin-left: 6px; color: var(--color-danger);" aria-label="Retirer {{ $product->name }} de la carte" title="Retirer de la carte">
                                        <x-icon name="trash" size="15" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    {{-- Produits retirés de la carte : plus vendus, mais leurs anciennes ventes restent dans les rapports --}}
    @if ($archived->isNotEmpty())
        <details style="margin-top: 28px;">
            <summary style="cursor: pointer; font-weight: 600; color: var(--color-text-secondary); padding: 8px 0;">
                Voir les produits retirés ({{ $archived->count() }})
            </summary>
            <p style="font-size: 13px; color: var(--color-text-secondary); margin: 6px 0 12px;">
                Ils ne sont plus proposés à la vente. Leurs anciennes ventes restent dans vos rapports.
            </p>
            <div class="card" style="padding: 0; overflow: hidden;">
                @foreach ($archived as $product)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 20px; {{ $loop->last ? '' : 'border-bottom: 1px solid var(--color-border);' }}">
                        <div style="min-width: 0;">
                            <div style="font-weight: 600;">{{ $product->name }}</div>
                            <div style="font-size: 12px; color: var(--color-text-secondary);">
                                {{ $product->category?->name }} · {{ number_format($product->selling_price, 0, ',', ' ') }} FCFA · retiré le {{ $product->archived_at->format('d/m/Y') }}
                            </div>
                        </div>
                        <button wire:click="restoreProduct({{ $product->id }})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; white-space: nowrap;">Remettre sur la carte</button>
                    </div>
                @endforeach
            </div>
        </details>
    @endif
    <style>
        .rt { margin-bottom: 16px; padding: 16px; border: 1px solid var(--color-border); border-radius: 16px; background: var(--color-surface); }
        .rt-q { display: flex; gap: 12px; align-items: flex-start; }
        .rt-q strong { display: block; font-size: 14.5px; line-height: 1.35; }
        .rt-ico { width: 36px; height: 36px; border-radius: 11px; background: #E6F0FA; color: #2463B5; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .rt-hint { display: block; font-size: 12.5px; color: var(--color-text-secondary); margin-top: 3px; line-height: 1.45; }
        .rt-yn { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 14px; padding: 4px; background: var(--color-bg); border-radius: 12px; }
        .rt-yn button { border: 0; border-radius: 9px; min-height: 42px; font: inherit; font-size: 14.5px; font-weight: 700; cursor: pointer; background: transparent; color: var(--color-text-secondary); transition: background-color .15s, color .15s, box-shadow .15s; }
        .rt-yn button.on { background: var(--color-primary); color: #fff; box-shadow: 0 4px 12px -4px rgba(11,77,60,.45); }
        .rt-yn button:active, .rt-chips button:active { transform: scale(.97); }
        .rt-crate { margin-top: 16px; padding-top: 14px; border-top: 1px dashed var(--color-border); }
        .rt-label { display: block; font-size: 13.5px; font-weight: 700; margin-bottom: 8px; }
        .rt-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .rt-chips > button { min-width: 64px; min-height: 42px; padding: 0 16px; border-radius: 11px; border: 1.5px solid var(--color-border); background: var(--color-surface); font: inherit; font-size: 15px; font-weight: 700; color: var(--color-text); cursor: pointer; transition: border-color .15s, background-color .15s, color .15s; }
        .rt-chips > button:hover:not(.on) { border-color: var(--color-text-secondary); }
        .rt-chips > button.on { border-color: var(--color-primary); background: var(--color-mint); color: var(--color-primary); }
        .rt-other { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; color: var(--color-text-secondary); }
        .rt-other input { width: 72px; height: 42px; text-align: center; font-size: 16px; font-weight: 700; border-radius: 11px; }
        .rt-error { color: var(--color-danger); font-size: 12.5px; margin-top: 8px; font-weight: 600; }
        .rt-why { margin-top: 14px; font-size: 13px; }
        .rt-why summary { cursor: pointer; color: var(--color-accent); font-weight: 700; list-style: none; display: inline-flex; align-items: center; gap: 6px; }
        .rt-why summary::-webkit-details-marker { display: none; }
        .rt-why summary::before { content: '?'; width: 18px; height: 18px; border-radius: 50%; background: var(--color-accent); color: #fff; font-size: 11.5px; display: inline-flex; align-items: center; justify-content: center; }
        .rt-why p { margin: 8px 0 0; padding: 10px 12px; background: #FFF8F2; border-radius: 10px; line-height: 1.5; color: var(--color-text); }
        /* « Désactiver » : contour rouge, pas un gros bouton rouge qui fait peur */
        .pm-off { color: var(--color-danger) !important; border-color: #F1C4C4 !important; }
        .pm-off:hover { background: var(--color-danger-bg) !important; }
        /* « Fini pour aujourd'hui » : discret ; « Remettre en vente » reste bien visible */
        .pm-soldout.btn-secondary { background: var(--color-mint) !important; color: var(--color-primary) !important; border: 1px solid #CFE6DB !important; box-shadow: none !important; }
        .pm-soldout.btn-secondary:hover { background: #E1F0E8 !important; }
        /* Téléphone : boutons de chaque produit bien alignés */
        @media (max-width: 640px) {
            main table td.pm-actions { display: grid !important; grid-template-columns: 1fr 1fr 44px; gap: 8px !important; }
            main table td.pm-actions > * { width: 100%; margin: 0 !important; }
            main table td.pm-actions .pm-soldout { grid-column: 1 / -1; }
            main table td.pm-actions button[aria-label^="Retirer"] { padding: 0 !important; }
        }
        @media (max-width: 420px) {
            .rt { padding: 14px; }
            .rt-chips > button { flex: 1; min-width: 0; }
            .rt-other { flex-basis: 100%; }
            .rt-other input { width: 96px; }
        }
    </style>
</div>
