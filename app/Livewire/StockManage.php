<?php

namespace App\Livewire;

use App\Actions\StockService;
use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Stock (patron et gérante) : vue d'ensemble, livraisons, pertes et
 * historique de tous les mouvements. Le comptage a son propre écran (/comptage).
 */
class StockManage extends Component
{
    use \App\Livewire\Concerns\ShowsBusinessErrors;

    use WithPagination;

    public const TABS = [
        'overview' => 'Stock',
        'delivery' => 'Livraison',
        'loss' => 'Perte ou casse',
        'history' => 'Historique',
    ];

    public string $tab = 'overview';

    // Livraison
    public string $supplier = '';
    public bool $paidFromRegister = false;
    /** Photo du bon de livraison : obligatoire si la livraison est payée avec l'argent de la caisse. */
    public string $deliveryPhoto = '';
    /** @var array<int, array{quantity: string, unit_cost: string}> */
    public array $deliveryLines = [];

    // Perte
    public ?int $lossProductId = null;
    public ?int $lossQuantity = 1;
    public string $lossReason = '';
    /** Photo de la perte, déjà réduite par le téléphone (JPEG en base64). Obligatoire. */
    public string $lossPhoto = '';

    // Historique
    public string $historyType = '';

    public function mount(): void
    {
        abort_unless(StockService::canView(auth()->user()), 403);
        $this->resetDeliveryLines();
    }

    protected function products()
    {
        // Un produit rangé disparaît du stock une fois écoulé ; s'il en reste, on le garde pour le compter
        // Les plats sans suivi de stock ne se livrent ni ne se comptent
        return Product::with('category')->where('track_stock', true)->where(fn ($q) => $q->whereNull('archived_at')->orWhere('stock_quantity', '>', 0))->get()
            ->sortBy(fn (Product $p) => [($p->category?->kind ?? 'food') === 'drink' ? 0 : 1, $p->category?->sort_order ?? 999, mb_strtolower($p->name)])
            ->values();
    }

    protected function resetDeliveryLines(): void
    {
        $this->deliveryLines = [];

        foreach ($this->products() as $p) {
            $this->deliveryLines[$p->id] = ['quantity' => '', 'unit_cost' => $p->cost_price !== null ? (string) $p->cost_price : ''];
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'overview';
        $this->resetValidation();
    }

    public function updatedHistoryType(): void
    {
        $this->resetPage();
    }

    public function saveDelivery(StockService $stock): void
    {
        $this->validate([
            'supplier' => 'nullable|string|max:120',
            'deliveryLines.*.quantity' => 'nullable|integer|min:0|max:100000',
            'deliveryLines.*.unit_cost' => 'nullable|integer|min:0|max:10000000',
        ], [
            'deliveryLines.*.quantity.*' => 'Entrez une quantité en nombre entier, sans virgule.',
            'deliveryLines.*.unit_cost.*' => 'Entrez le prix d\'achat en FCFA, sans virgule.',
        ]);

        $lines = collect($this->deliveryLines)
            ->filter(fn ($l) => (int) ($l['quantity'] ?? 0) > 0)
            ->map(fn ($l) => ['quantity' => (int) $l['quantity'], 'unit_cost' => $l['unit_cost'] === '' ? null : (int) $l['unit_cost']])
            ->all();

        $receiptPath = null;

        if ($this->paidFromRegister) {
            if ($this->deliveryPhoto === '') {
                $this->addError('deliveryPhoto', 'Prenez une photo du bon de livraison : l\'argent sort de la caisse.');

                return;
            }

            [$receiptPath, $photoError] = \App\Support\PhotoStore::store($this->deliveryPhoto, 'recus', (string) auth()->user()->currentRestaurantId(), 1600, 900_000);

            if ($photoError) {
                $this->addError('deliveryPhoto', $photoError);

                return;
            }
        }

        try {
            $result = $stock->recordDelivery(auth()->user(), $lines, trim($this->supplier) ?: null, $this->paidFromRegister, $receiptPath);
        } catch (BusinessRuleException $e) {
            \App\Support\PhotoStore::delete($receiptPath);
            $this->addError('delivery', $e->getMessage());

            return;
        }

        session()->flash('success', "Livraison enregistrée : {$result['quantity']} " . ($result['quantity'] > 1 ? 'articles' : 'article') . " sur {$result['lines']} " . ($result['lines'] > 1 ? 'produits' : 'produit')
            . ($result['total_cost'] > 0 ? ', ' . number_format($result['total_cost'], 0, ',', ' ') . ' FCFA' : '')
            . ($result['expense'] ? ', dépense créée dans la caisse.' : '.'));

        // Plus de vides rendus que prévu : à montrer tout de suite au patron
        if ($result['empties_alerts'] !== []) {
            session()->flash('empties_alerts', $result['empties_alerts']);
        }

        $this->supplier = '';
        $this->paidFromRegister = false;
        $this->deliveryPhoto = '';
        $this->resetDeliveryLines();
        $this->tab = 'overview';
    }

    public function saveLoss(StockService $stock): void
    {
        $this->validate([
            'lossProductId' => 'required|integer',
            'lossQuantity' => 'required|integer|min:1|max:100000',
            'lossReason' => 'required|string|min:3|max:255',
            'lossPhoto' => 'required|string',
        ], [
            'lossProductId.required' => 'Choisissez le produit.',
            'lossReason.required' => 'Indiquez le motif.',
            'lossReason.min' => 'Indiquez le motif.',
            'lossPhoto.required' => 'Prenez une photo de la perte (les bouteilles cassées, le produit périmé).',
        ]);

        [$photoPath, $photoError] = \App\Support\PhotoStore::store($this->lossPhoto, 'pertes', (string) auth()->user()->currentRestaurantId(), 1600, 900_000);

        if ($photoError) {
            $this->addError('lossPhoto', $photoError);

            return;
        }

        try {
            $movement = $stock->recordLoss(auth()->user(), $this->lossProductId, $this->lossQuantity, $this->lossReason, $photoPath);
        } catch (BusinessRuleException $e) {
            \App\Support\PhotoStore::delete($photoPath);
            $this->addError('lossQuantity', $e->getMessage());

            return;
        }

        session()->flash('success', 'Perte enregistrée : ' . abs($movement->quantity) . ' ' . $movement->product->name
            . ', soit ' . number_format(abs($movement->quantity) * $movement->unit_cost, 0, ',', ' ') . ' FCFA.');

        $this->reset(['lossProductId', 'lossReason', 'lossPhoto']);
        $this->lossQuantity = 1;
        $this->tab = 'overview';
    }

    public function render()
    {
        $products = $this->products();

        return view('livewire.stock-manage', [
            'tabs' => self::TABS,
            'products' => $products,
            'categories' => Category::orderBy('sort_order')->get(),
            'stockValue' => $products->sum(fn (Product $p) => max(0, $p->stock_quantity) * $p->unitValue()),
            'stockSaleValue' => $products->sum(fn (Product $p) => max(0, $p->stock_quantity) * $p->selling_price),
            'lowCount' => $products->filter->isLowStock()->count(),
            'countDays' => (new \App\Support\StockCountSheet(auth()->user()->currentRestaurantId()))->daysSince(),
            'missingCost' => $products->whereNull('cost_price')->count(),
            'recentCounts' => StockCount::with('user')->latest()->latest('id')->limit(5)->get(),
            'movements' => $this->tab === 'history'
                ? StockMovement::with(['product', 'user'])
                    ->when(array_key_exists($this->historyType, StockMovement::TYPES), fn ($q) => $q->where('type', $this->historyType))
                    ->latest()
                    ->paginate(30)
                : null,
            'movementTypes' => StockMovement::TYPES,
        ]);
    }
}
