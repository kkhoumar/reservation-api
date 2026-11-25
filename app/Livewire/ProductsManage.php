<?php

namespace App\Livewire;

use App\Actions\StockService;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Gestion du menu/produits (PRD section 10, MVP v3 priorité #2).
 * Permet au owner/manager de gérer sa carte sans intervention technique.
 */
class ProductsManage extends Component
{
    public bool $showProductForm = false;
    public bool $showCategoryForm = false;
    #[Locked]
    public ?int $editingProductId = null;

    public string $name = '';
    public string $sellingPrice = '';
    public ?int $categoryId = null;
    public string $costPrice = '';
    public string $stockQuantity = '';
    public string $stockAlertThreshold = '';
    public bool $isAvailable = true;

    /** Compté à l'unité (boissons) ; un plat cuisiné ne l'est pas. */
    public bool $trackStock = true;

    /** Bouteille en verre rendue au fournisseur (1 vide contre 1 pleine). */
    public bool $isReturnable = false;
    public string $crateSize = '';

    public string $newCategoryName = '';
    public string $newCategoryKind = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sellingPrice' => 'required|integer|min:0',
            // La règle "exists" interroge directement la base, sans le filtre
            // automatique : on la limite explicitement au restaurant.
            'categoryId' => [
                'required',
                Rule::exists('categories', 'id')->where('restaurant_id', auth()->user()->currentRestaurantId()),
            ],
            // Obligatoire : sans prix d'achat, pas de bénéfice juste ni de comptage chiffré
            'costPrice' => 'required|integer|min:0|max:10000000|lte:sellingPrice',
            // Le stock ne se saisit qu'à la création ; ensuite il ne bouge que
            // par les ventes, livraisons, pertes et inventaires (écran Stock)
            'stockQuantity' => $this->editingProductId || ! $this->trackStock ? 'nullable' : 'required|integer|min:0|max:1000000',
            'stockAlertThreshold' => 'nullable|integer|min:0',
            'isReturnable' => 'boolean',
            // Casier : 12 et 24 en boutons, « Autre » pour écrire son nombre
            'crateSize' => $this->trackStock && $this->isReturnable ? 'required|integer|min:1|max:50' : 'nullable',
        ];
    }

    protected function messages(): array
    {
        return [
            'sellingPrice.required' => 'Entrez le prix de vente.',
            'costPrice.required' => 'Entrez le prix d\'achat : il sert à calculer votre bénéfice.',
            'costPrice.lte' => 'Le prix d\'achat dépasse le prix de vente : vous perdriez de l\'argent à chaque vente. Vérifiez les deux prix.',
            'crateSize.required' => 'Dites combien de bouteilles il y a dans un casier.',
            'crateSize.integer' => 'Un casier a entre 1 et 50 bouteilles. Vérifiez le nombre.',
            'crateSize.min' => 'Un casier a entre 1 et 50 bouteilles. Vérifiez le nombre.',
            'crateSize.max' => 'Un casier a entre 1 et 50 bouteilles. Vérifiez le nombre.',
        ];
    }

    public function openCreateForm()
    {
        $this->reset(['editingProductId', 'name', 'sellingPrice', 'costPrice', 'categoryId', 'stockQuantity', 'stockAlertThreshold', 'isReturnable', 'crateSize']);
        $this->isAvailable = true;
        $this->trackStock = true;
        $this->showProductForm = true;
    }

    public function editProduct($productId)
    {
        $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($productId);

        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->sellingPrice = (string) $product->selling_price;
        $this->costPrice = $product->cost_price !== null ? (string) $product->cost_price : '';
        $this->categoryId = $product->category_id;
        $this->stockQuantity = (string) $product->stock_quantity;
        $this->stockAlertThreshold = $product->stock_alert_threshold !== null ? (string) $product->stock_alert_threshold : '';
        $this->isAvailable = $product->is_available;
        $this->trackStock = (bool) $product->track_stock;
        $this->isReturnable = (bool) $product->is_returnable;
        $this->crateSize = $product->crate_size ? (string) $product->crate_size : '';
        $this->showProductForm = true;
    }

    public function saveProduct()
    {
        $this->validate();

        $data = [
            'restaurant_id' => auth()->user()->currentRestaurantId(),
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'selling_price' => $this->sellingPrice,
            'cost_price' => (int) $this->costPrice,
            'stock_alert_threshold' => $this->stockAlertThreshold !== '' ? $this->stockAlertThreshold : null,
            'is_available' => $this->isAvailable,
            'track_stock' => $this->trackStock,
            // Un plat sans suivi de stock ne laisse pas de bouteille vide
            'is_returnable' => $this->trackStock && $this->isReturnable,
            'crate_size' => $this->trackStock && $this->isReturnable && $this->crateSize !== '' ? (int) $this->crateSize : null,
        ];

        if ($this->editingProductId) {
            // Le stock n'est jamais modifié ici (pas de correction silencieuse)
            $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($this->editingProductId);
            $oldSale = (int) $product->selling_price;
            $oldCost = $product->cost_price;
            $product->update($data);

            $changes = [];
            if ($oldSale !== (int) $product->selling_price) {
                $changes[] = 'prix de vente ' . \App\Models\ActivityLog::fcfa($oldSale) . ' → ' . \App\Models\ActivityLog::fcfa((int) $product->selling_price);
            }
            if ($oldCost !== $product->cost_price) {
                $changes[] = "prix d'achat " . ($oldCost === null ? 'vide' : \App\Models\ActivityLog::fcfa($oldCost)) . ' → ' . \App\Models\ActivityLog::fcfa((int) $product->cost_price);
            }
            if ($changes !== []) {
                \App\Models\ActivityLog::record('price_changed', "a changé le prix de {$product->name} : " . implode(', ', $changes) . '.', ['meta' => ['product_id' => $product->id]]);
            }
        } else {
            DB::transaction(function () use ($data) {
                $product = Product::create($data + ['stock_quantity' => $this->trackStock ? (int) $this->stockQuantity : 0]);
                app(StockService::class)->recordInitial(auth()->user(), $product);
                \App\Models\ActivityLog::record('product_created', "a ajouté le produit {$product->name} à " . \App\Models\ActivityLog::fcfa((int) $product->selling_price) . ", stock de départ {$product->stock_quantity}.", ['meta' => ['product_id' => $product->id]]);
            });
        }

        $this->showProductForm = false;
        session()->flash('success', 'Produit enregistré.');
    }

    /** Nouveau produit : on propose « Suivre le stock » pour les boissons, pas pour la nourriture. */
    public function updatedCategoryId($value): void
    {
        if ($this->editingProductId || ! $value) {
            return;
        }

        $kind = Category::where('restaurant_id', auth()->user()->currentRestaurantId())->whereKey($value)->value('kind');
        $this->trackStock = $kind !== 'food';
    }

    /** « Vous rendez les bouteilles vides au dépôt ? » Oui / Non. À « Oui », 12 est proposé (le casier le plus courant). */
    public function setReturnable(bool $yes): void
    {
        $this->isReturnable = $yes;
        if ($yes && $this->crateSize === '') {
            $this->crateSize = '12';
        }
        $this->resetErrorBag('crateSize');
    }

    /** « Fini pour aujourd'hui » : la cuisine n'en a plus ; il revient tout seul demain. */
    public function toggleSoldOut($productId): void
    {
        $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($productId);
        $product->forceFill(['sold_out_on' => $product->isSoldOutToday() ? null : today()])->save();
    }

    public function toggleAvailability($productId)    {
        $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($productId);
        $product->update(['is_available' => !$product->is_available]);
    }

    /**
     * « Supprimer » : jamais vendu → effacé pour de bon ; déjà vendu → rangé
     * dans les archives, pour que les anciennes ventes restent justes.
     */
    public function deleteProduct($productId): void
    {
        $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($productId);

        if ($product->isInOpenOrder()) {
            session()->flash('error', "« {$product->name} » est dans une commande pas encore payée. Encaissez ou annulez cette commande, puis recommencez.");

            return;
        }

        if ($this->editingProductId === $product->id) {
            $this->showProductForm = false;
            $this->editingProductId = null;
        }

        if (! $product->hasHistory()) {
            $product->delete();
            \App\Models\ActivityLog::record('product_removed', "a retiré le produit {$product->name} de la carte (jamais vendu, effacé).");
            session()->flash('success', "« {$product->name} » est retiré de la carte.");

            return;
        }

        $product->forceFill(['archived_at' => now(), 'is_available' => false])->save();
        \App\Models\ActivityLog::record('product_removed', "a retiré le produit {$product->name} de la carte (stock restant : {$product->stock_quantity}).", ['meta' => ['product_id' => $product->id]]);
        session()->flash('success', "« {$product->name} » est retiré de la carte. Ses anciennes ventes restent dans vos rapports.");
    }

    public function restoreProduct($productId): void
    {
        $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($productId);
        $product->forceFill(['archived_at' => null, 'is_available' => true])->save();
        \App\Models\ActivityLog::record('product_restored', "a remis le produit {$product->name} sur la carte.", ['meta' => ['product_id' => $product->id]]);
        session()->flash('success', "« {$product->name} » est de retour sur la carte.");
    }

    public function saveCategory()
    {
        $this->validate([
            'newCategoryName' => 'required|string|max:255',
            'newCategoryKind' => 'nullable|in:' . implode(',', array_keys(Category::KINDS)),
        ]);

        Category::create([
            'restaurant_id' => auth()->user()->currentRestaurantId(),
            'name' => $this->newCategoryName,
            'kind' => $this->newCategoryKind ?: Category::guessKind($this->newCategoryName),
            'sort_order' => Category::where('restaurant_id', auth()->user()->currentRestaurantId())->count() + 1,
        ]);

        $this->newCategoryName = '';
        $this->newCategoryKind = '';
        $this->showCategoryForm = false;
        session()->flash('success', 'Catégorie ajoutée.');
    }

    public function setCategoryKind($categoryId, string $kind): void
    {
        if (! array_key_exists($kind, Category::KINDS)) {
            return;
        }

        $category = Category::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($categoryId);
        $category->update(['kind' => $kind]);

        // Des plats ne se comptent pas comme des bouteilles : plus de faux « Épuisé »
        // (l'inverse n'est pas fait : un plat sans stock tomberait « épuisé » d'un coup)
        if ($kind === 'food') {
            Product::where('category_id', $category->id)->update(['track_stock' => false]);
        }
    }

    public function render()
    {
        $categories = Category::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->with(['products' => fn ($q) => $q->active()->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        $archived = Product::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereNotNull('archived_at')
            ->with('category')
            ->orderBy('name')
            ->get();

        return view('livewire.products-manage', ['categories' => $categories, 'archived' => $archived]);
    }
}