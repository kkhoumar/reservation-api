<div x-data="{
        q: '',
        norm(s) { return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
        has(names) { const q = this.norm(this.q); return !q || names.some(n => this.norm(n).includes(q)); },
    }">
    <x-flash />

    <div class="page-head pm-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; margin-bottom: 20px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Carte
            </div>
            <h1>Menu & produits</h1>
        </div>
        <div class="pm-head-btns" style="display: flex; gap: 10px;">
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

    @php $allProducts = $categories->flatMap->products; @endphp
    @if ($categories->isEmpty())
        <div class="card" style="text-align: center; padding: 26px 20px; margin-bottom: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 16px; background: var(--color-mint); color: var(--color-primary); display: grid; place-items: center; margin: 0 auto 12px;"><x-icon name="bottle" size="24" /></div>
            <h3 style="margin: 0 0 6px;">Votre menu est vide</h3>
            <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0 auto 4px; max-width: 420px; line-height: 1.55;">
                <strong>1.</strong> Créez une catégorie, par exemple <em>Bières</em>, <em>Sucreries</em> ou <em>Plats</em>.<br>
                <strong>2.</strong> Ajoutez vos produits dedans, avec le prix de vente et le prix d'achat.
            </p>
            <button wire:click="$set('showCategoryForm', true)" class="btn btn-primary" style="margin-top: 12px;">+ Créer ma première catégorie</button>
        </div>
    @endif    @if ($allProducts->count() >= 6)
        <div class="pm-search">
            <x-icon name="search" size="18" />
            <input type="search" x-model="q" placeholder="Chercher un produit…" aria-label="Chercher un produit" autocomplete="off" enterkeyhint="search">
            <button type="button" x-show="q" x-cloak @click="q = ''" aria-label="Effacer la recherche"><x-icon name="x" size="16" /></button>
        </div>
        <div class="pm-noresult card" x-show="q && !has(@js($allProducts->pluck('name')->values()))" x-cloak>
            Aucun produit ne s'appelle « <span x-text="q"></span> ».
        </div>
    @endif

    @if ($showCategoryForm)
        <div class="pm-sheet" role="dialog" aria-modal="true" aria-label="Nouvelle catégorie" @keydown.escape.window="$wire.set('showCategoryForm', false)">
        <div class="pm-sheet-bg" wire:click="$set('showCategoryForm', false)"></div>
        <div class="card pm-sheet-card" style="max-width: 420px;">
            <div class="pm-sheet-head">
                <h3>Nouvelle catégorie</h3>
                <button type="button" class="pm-x" wire:click="$set('showCategoryForm', false)" aria-label="Fermer"><x-icon name="x" size="18" /></button>
            </div>
            <label style="font-size: 13px; font-weight: 600;">Nom</label>
            <input type="text" wire:model="newCategoryName" placeholder="Ex : Grillades" style="width: 100%; margin: 6px 0 14px 0;">
            @error('newCategoryName') <div class="pm-err" style="color: var(--color-danger); font-size: 12.5px; font-weight: 600; margin-top: 4px; margin-bottom: 10px;">{{ $message }}</div> @enderror
            <label style="font-size: 13px; font-weight: 600;">Famille</label>
            <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 2px;">Le comptage sépare les totaux des boissons et de la nourriture.</div>
            <div style="display: flex; gap: 8px; margin: 6px 0 16px 0;">
                @foreach (\App\Models\Category::KINDS as $kind => $kindLabel)
                    <button type="button" wire:click="$set('newCategoryKind', '{{ $kind }}')" class="btn {{ $newCategoryKind === $kind ? 'btn-secondary' : 'btn-ghost' }}" style="flex: 1; justify-content: center;">{{ $kindLabel }}</button>
                @endforeach
            </div>
            <div class="pm-sheet-actions">
                <button wire:click="saveCategory" wire:loading.attr="disabled" wire:target="saveCategory" class="btn btn-primary" style="flex: 1;">Enregistrer</button>
                <button wire:click="$set('showCategoryForm', false)" class="btn btn-ghost" style="flex: 1;">Annuler</button>
            </div>
        </div>
        </div>
    @endif

    @if ($showProductForm)
        <div class="pm-sheet" role="dialog" aria-modal="true" aria-label="{{ $editingProductId ? 'Modifier le produit' : 'Nouveau produit' }}" @keydown.escape.window="$wire.set('showProductForm', false)">
        <div class="pm-sheet-bg" wire:click="$set('showProductForm', false)"></div>
        <div class="card pm-sheet-card">
            <div class="pm-sheet-head">
                <h3>{{ $editingProductId ? 'Modifier « ' . $name . ' »' : 'Nouveau produit' }}</h3>
                <button type="button" class="pm-x" wire:click="$set('showProductForm', false)" aria-label="Fermer"><x-icon name="x" size="18" /></button>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 600;">Nom</label>
                <input type="text" wire:model="name" style="width: 100%; margin-top: 6px;">
                @error('name') <div class="pm-err" style="color: var(--color-danger); font-size: 12.5px; font-weight: 600; margin-top: 4px;">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 600;">Catégorie</label>
                <select wire:model.live="categoryId" style="width: 100%; margin-top: 6px;">
                    <option value="">-- Choisir --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('categoryId') <div class="pm-err" style="color: var(--color-danger); font-size: 12.5px; font-weight: 600; margin-top: 4px;">{{ $message }}</div> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Prix de vente (FCFA)</label>
                    <x-money-input model="sellingPrice" :empty="''" style="width: 100%; margin-top: 6px;" />
                    @error('sellingPrice') <div class="pm-err" style="color: var(--color-danger); font-size: 12.5px; font-weight: 600; margin-top: 4px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Prix d'achat (FCFA)</label>
                    <x-money-input model="costPrice" :empty="''" placeholder="Obligatoire" style="width: 100%; margin-top: 6px;" />
                    @error('costPrice') <div class="pm-err" style="color: var(--color-danger); font-size: 12.5px; font-weight: 600; margin-top: 4px;">{{ $message }}</div> @enderror
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
                            @error('stockQuantity') <div class="pm-err" style="color: var(--color-danger); font-size: 12.5px; font-weight: 600; margin-top: 4px;">{{ $message }}</div> @enderror
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
                        @error('crateSize') <div class="rt-error pm-err">{{ $message }}</div> @enderror

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

            <div class="pm-sheet-actions">
                <button type="button" class="btn btn-primary" style="flex: 1;" wire:loading.attr="disabled" wire:target="saveProduct"
                        @click="$wire.saveProduct().then(() => $nextTick(() => { const e = document.querySelector('.pm-sheet .pm-err'); if (e) e.scrollIntoView({ block: 'center', behavior: 'smooth' }); }))">Enregistrer</button>
                <button wire:click="$set('showProductForm', false)" class="btn btn-ghost" style="flex: 1;">Annuler</button>
            </div>
        </div>
        </div>
    @endif

    @foreach ($categories as $category)
        <section wire:key="cat-{{ $category->id }}" x-show="!q || has(@js($category->products->pluck('name')->values()))">
        <div class="pm-cat-head">
            <h3>{{ $category->name }} <span class="pm-count">{{ $category->products->count() }}</span></h3>
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
                <table class="pm-table" style="width: 100%; border-collapse: collapse;">
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
                            <tr style="border-bottom: 1px solid var(--color-border);" x-show="has([@js($product->name)])">
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
                                            data-confirm-ok="Retirer de la carte" wire:confirm="Retirer « {{ $product->name }} » de la carte ?&#10;&#10;Vos anciennes ventes restent dans vos rapports. Vous pourrez le remettre sur la carte quand vous voulez."
                                            class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px; margin-left: 6px; color: var(--color-danger);" aria-label="Retirer {{ $product->name }} de la carte" title="Retirer de la carte">
                                        <x-icon name="trash" size="15" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Téléphone : une ligne courte par produit ; toucher la ligne = modifier --}}
                <div class="pm-list">
                    @foreach ($category->products as $product)
                        @php
                            $soldOut = $product->is_available && $product->isSoldOutToday();
                            $low = $product->track_stock && $product->isLowStock();
                        @endphp
                        <div class="pm-item {{ $product->is_available ? '' : 'off' }}" wire:key="pmi-{{ $product->id }}" x-show="has([@js($product->name)])">
                            <button type="button" class="pm-main" wire:click="editProduct({{ $product->id }})" aria-label="Modifier {{ $product->name }}">
                                <span class="pm-ava {{ $category->kind === 'food' ? 'food' : 'drink' }}"><x-icon :name="$category->kind === 'food' ? 'utensils' : 'bottle'" size="18" /></span>
                                <span class="pm-txt">
                                    <span class="pm-name">{{ $product->name }}</span>
                                    <span class="pm-meta">
                                        @if (! $product->is_available)
                                            <span class="pm-pill off">Désactivé</span>
                                        @elseif ($soldOut)
                                            <span class="pm-pill sold">Fini aujourd'hui</span>
                                        @endif
                                        @if (! $product->track_stock)
                                            <span>Stock non suivi</span>
                                        @elseif ($low)
                                            <span class="pm-low"><x-icon name="alert" size="13" /> {{ $product->stock_quantity }} en stock</span>
                                        @else
                                            <span>{{ $product->stock_quantity }} en stock</span>
                                        @endif
                                    </span>
                                    @if ($product->cost_price === null)
                                        <span class="pm-warn">Prix d'achat manquant</span>
                                    @endif
                                </span>
                                <span class="pm-price">{{ number_format($product->selling_price, 0, ',', ' ') }}<small> F</small></span>
                                <span class="pm-chev"><x-icon name="chevron-right" size="18" /></span>
                            </button>

                            <div class="pm-more" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                <button type="button" class="pm-dots" @click="open = !open" :aria-expanded="open" aria-label="Plus d'actions pour {{ $product->name }}"><x-icon name="dots" size="20" /></button>
                                <div class="pm-menu" x-show="open" x-transition.origin.top.right x-cloak>
                                    <button type="button" wire:click="editProduct({{ $product->id }})" @click="open = false"><x-icon name="pencil" size="17" /> Modifier</button>
                                    <button type="button" wire:click="toggleAvailability({{ $product->id }})" @click="open = false">
                                        <x-icon :name="$product->is_available ? 'eye-off' : 'eye'" size="17" />
                                        {{ $product->is_available ? 'Ne plus vendre (désactiver)' : 'Remettre en vente (activer)' }}
                                    </button>
                                    <button type="button" class="danger" @click="open = false" wire:click="deleteProduct({{ $product->id }})"
                                            data-confirm-ok="Retirer de la carte" wire:confirm="Retirer « {{ $product->name }} » de la carte ?&#10;&#10;Vos anciennes ventes restent dans vos rapports. Vous pourrez le remettre sur la carte quand vous voulez.">
                                        <x-icon name="trash" size="17" /> Retirer de la carte
                                    </button>
                                </div>
                            </div>

                            @if (! $product->track_stock && $product->is_available)
                                <button type="button" wire:click="toggleSoldOut({{ $product->id }})" class="pm-sold {{ $soldOut ? 'on' : '' }}">
                                    @if ($soldOut)
                                        <x-icon name="check" size="16" stroke="2.4" /> Remettre en vente aujourd'hui
                                    @else
                                        <x-icon name="clock" size="16" /> Fini pour aujourd'hui
                                    @endif
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        </section>
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
        /* ---------- Recherche ---------- */
        .pm-search { position: relative; display: flex; align-items: center; margin: 0 0 6px; }
        .pm-search > svg { position: absolute; left: 14px; color: var(--color-text-secondary); pointer-events: none; }
        .pm-search input { width: 100%; height: 48px; padding: 0 44px 0 42px; border-radius: 14px; font-size: 16px; background: var(--color-surface); }
        .pm-search input::-webkit-search-cancel-button { display: none; }
        .pm-search button { position: absolute; right: 8px; width: 32px; height: 32px; border: 0; border-radius: 50%; background: var(--color-bg); color: var(--color-text-secondary); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .pm-noresult { margin-top: 12px; color: var(--color-text-secondary); text-align: center; }

        /* ---------- En-tête de catégorie ---------- */
        .pm-cat-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin: 24px 0 10px; }
        .pm-cat-head h3 { margin: 0; color: var(--color-text-secondary); display: inline-flex; align-items: center; gap: 8px; }
        .pm-count { font-size: 12px; font-weight: 700; background: var(--color-bg); border: 1px solid var(--color-border); color: var(--color-text-secondary); border-radius: 999px; padding: 1px 8px; }

        /* ---------- Fenêtre (formulaires) : au-dessus de la page, jamais perdue en haut ---------- */
        body:has(.pm-sheet) { overflow: hidden; }
        .pm-sheet { position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .pm-sheet-bg { position: absolute; inset: 0; background: rgba(10, 28, 22, .45); backdrop-filter: blur(2px); animation: pm-fade .18s ease-out; }
        .pm-sheet-card { position: relative; width: 100%; max-width: 480px; max-height: calc(100dvh - 48px); overflow-y: auto; overflow-x: hidden; margin: 0 !important; padding: 20px 24px 0 !important; overscroll-behavior: contain; animation: pm-pop .2s ease-out; }
        .pm-sheet-head { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--color-surface); margin: -4px 0 12px; padding: 4px 0 10px; border-bottom: 1px solid var(--color-border); }
        .pm-sheet-head h3 { margin: 0; font-size: 17px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pm-x { flex-shrink: 0; width: 38px; height: 38px; border-radius: 50%; border: 0; background: var(--color-bg); color: var(--color-text); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .pm-sheet-actions { position: sticky; bottom: 0; z-index: 2; display: flex; gap: 10px; background: var(--color-surface); margin: 0 -24px; padding: 12px 24px calc(14px + env(safe-area-inset-bottom)); border-top: 1px solid var(--color-border); }
        @keyframes pm-fade { from { opacity: 0; } }
        @keyframes pm-pop { from { opacity: 0; transform: translateY(12px) scale(.98); } }
        @keyframes pm-up { from { transform: translateY(100%); } }

        /* ---------- Liste téléphone ---------- */
        .pm-list { display: none; }
        .pm-item { position: relative; display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; border-bottom: 1px solid var(--color-border); }
        .pm-item:last-child { border-bottom: 0; }
        .pm-main { display: flex; align-items: center; gap: 12px; min-width: 0; padding: 14px 4px 14px 16px; border: 0; background: transparent; font: inherit; color: inherit; text-align: left; cursor: pointer; -webkit-tap-highlight-color: transparent; }
        .pm-main:active { background: var(--color-bg); }
        .pm-ava { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; }
        .pm-ava.drink { background: #E6F0FA; color: #2463B5; }
        .pm-ava.food { background: #FCEFE6; color: var(--color-accent); }
        .pm-txt { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
        .pm-name { font-size: 15.5px; font-weight: 700; line-height: 1.25; overflow-wrap: anywhere; }
        .pm-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; font-size: 13px; color: var(--color-text-secondary); }
        .pm-low { color: var(--color-danger); font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
        .pm-warn { font-size: 12.5px; font-weight: 600; color: var(--color-accent); }
        .pm-pill { font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 2px 8px; }
        .pm-pill.off { background: var(--color-danger-bg); color: var(--color-danger); }
        .pm-pill.sold { background: #FFF1E3; color: #A84A1C; }
        .pm-price { flex-shrink: 0; font-size: 15.5px; font-weight: 800; color: var(--color-text); white-space: nowrap; }
        .pm-price small { font-size: 12px; font-weight: 700; color: var(--color-text-secondary); }
        .pm-chev { flex-shrink: 0; color: #B6C2BD; display: inline-flex; margin-left: -4px; }
        .pm-item.off .pm-name, .pm-item.off .pm-price { color: var(--color-text-secondary); }
        .pm-item.off .pm-ava { filter: grayscale(1); opacity: .6; }
        .pm-more { position: relative; padding-right: 8px; }
        .pm-dots { width: 40px; height: 44px; border: 0; border-radius: 12px; background: transparent; color: var(--color-text-secondary); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .pm-dots:active, .pm-dots[aria-expanded="true"] { background: var(--color-bg); color: var(--color-text); }
        .pm-menu { position: absolute; right: 8px; top: calc(100% - 2px); z-index: 30; min-width: 250px; padding: 6px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; box-shadow: 0 14px 34px -10px rgba(16,35,29,.35); }
        .pm-menu button { width: 100%; display: flex; align-items: center; gap: 10px; min-height: 46px; padding: 0 12px; border: 0; border-radius: 10px; background: transparent; font: inherit; font-size: 14.5px; font-weight: 600; color: var(--color-text); text-align: left; cursor: pointer; }
        .pm-menu button:active, .pm-menu button:hover { background: var(--color-bg); }
        .pm-menu button.danger { color: var(--color-danger); }
        .pm-sold { grid-column: 1 / -1; margin: -4px 16px 14px 68px; min-height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; border-radius: 11px; border: 1px solid #CFE6DB; background: var(--color-mint); color: var(--color-primary); font: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer; }
        .pm-sold.on { background: var(--color-accent); border-color: var(--color-accent); color: #fff; }

        @media (max-width: 640px) {
            .pm-head { flex-direction: column; align-items: stretch !important; }
            .pm-head-btns > .btn { flex: 1; justify-content: center; }
            .pm-table { display: none !important; }
            .pm-list { display: block; }
            .pm-cat-head { margin-top: 20px; }
            /* Fenêtre : feuille qui monte du bas, comme les applications de téléphone */
            .pm-sheet { align-items: flex-end; padding: 0; }
            .pm-sheet-card { max-width: none; max-height: 92dvh; border-radius: 22px 22px 0 0 !important; padding: 16px 16px 0 !important; animation: pm-up .24s cubic-bezier(.2,.8,.2,1); }
            .pm-sheet-card::before { content: ''; display: block; width: 40px; height: 4px; border-radius: 4px; background: var(--color-border); margin: -6px auto 10px; }
            .pm-sheet-head { top: -16px; padding-top: 12px; margin-top: -12px; }
            .pm-sheet-actions { margin: 0 -16px; padding: 12px 16px calc(12px + env(safe-area-inset-bottom)); }
        }

        @media (max-width: 380px) {
            .pm-chev { display: none; }
            .pm-main { gap: 10px; padding-left: 14px; }
            .pm-sold { margin-left: 64px; }
        }

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
