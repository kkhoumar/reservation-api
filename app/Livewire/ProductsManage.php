<?php

namespace App\Livewire;

use App\Models\Category;
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
    public string $stockQuantity = '';
    public string $stockAlertThreshold = '';
    public bool $isAvailable = true;

    public string $newCategoryName = '';

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
            'stockQuantity' => 'required|integer|min:0',
            'stockAlertThreshold' => 'nullable|integer|min:0',
        ];
    }

    public function openCreateForm()
    {
        $this->reset(['editingProductId', 'name', 'sellingPrice', 'categoryId', 'stockQuantity', 'stockAlertThreshold']);
        $this->isAvailable = true;
        $this->showProductForm = true;
    }

    public function editProduct($productId)
    {
        $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($productId);

        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->sellingPrice = (string) $product->selling_price;
        $this->categoryId = $product->category_id;
        $this->stockQuantity = (string) $product->stock_quantity;
        $this->stockAlertThreshold = $product->stock_alert_threshold !== null ? (string) $product->stock_alert_threshold : '';
        $this->isAvailable = $product->is_available;
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
            'stock_quantity' => $this->stockQuantity,
            'stock_alert_threshold' => $this->stockAlertThreshold !== '' ? $this->stockAlertThreshold : null,
            'is_available' => $this->isAvailable,
        ];

        if ($this->editingProductId) {
            Product::where('restaurant_id', auth()->user()->currentRestaurantId())
                ->findOrFail($this->editingProductId)
                ->update($data);
        } else {
            Product::create($data);
        }

        $this->showProductForm = false;
        session()->flash('success', 'Produit enregistré.');
    }

    public function toggleAvailability($productId)
    {
        $product = Product::where('restaurant_id', auth()->user()->currentRestaurantId())->findOrFail($productId);
        $product->update(['is_available' => !$product->is_available]);
    }

    public function saveCategory()
    {
        $this->validate(['newCategoryName' => 'required|string|max:255']);

        Category::create([
            'restaurant_id' => auth()->user()->currentRestaurantId(),
            'name' => $this->newCategoryName,
            'sort_order' => Category::where('restaurant_id', auth()->user()->currentRestaurantId())->count() + 1,
        ]);

        $this->newCategoryName = '';
        $this->showCategoryForm = false;
        session()->flash('success', 'Catégorie ajoutée.');
    }

    public function render()
    {
        $categories = Category::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->with(['products' => fn ($q) => $q->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.products-manage', ['categories' => $categories]);
    }
}