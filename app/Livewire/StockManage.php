<?php

namespace App\Livewire;

use App\Actions\StockService;
use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Stock (patron et gérante) : vue d'ensemble, livraisons, pertes,
 * inventaire et historique de tous les mouvements.
 */
class StockManage extends Component
{
    use WithPagination;

    public const TABS = [
        'overview' => 'Stock',
        'delivery' => 'Livraison',
        'loss' => 'Perte / casse',
        'inventory' => 'Inventaire',
        'history' => 'Historique',
    ];

    public string $tab = 'overview';

    // Livraison
    public string $supplier = '';
    public bool $paidFromRegister = false;
    /** @var array<int, array{quantity: string, unit_cost: string}> */
    public array $deliveryLines = [];

    // Perte
    public ?int $lossProductId = null;
    public ?int $lossQuantity = 1;
    public string $lossReason = '';

    // Inventaire (comptage « à l'aveugle » : le stock attendu n'est pas affiché)
    /** @var array<int, string> */
    public array $counts = [];
    public string $inventoryNote = '';
    #[Locked]
    public ?int $lastCountId = null;

    // Historique
    public string $historyType = '';

    public function mount(): void
    {
        abort_unless(StockService::canManage(auth()->user()), 403);
        $this->resetDeliveryLines();
    }

    protected function products()
    {
        return Product::with('category')->orderBy('name')->get();
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
            'deliveryLines.*.quantity.*' => 'Quantité invalide.',
            'deliveryLines.*.unit_cost.*' => 'Prix invalide.',
        ]);

        $lines = collect($this->deliveryLines)
            ->filter(fn ($l) => (int) ($l['quantity'] ?? 0) > 0)
            ->map(fn ($l) => ['quantity' => (int) $l['quantity'], 'unit_cost' => $l['unit_cost'] === '' ? null : (int) $l['unit_cost']])
            ->all();

        try {
            $result = $stock->recordDelivery(auth()->user(), $lines, trim($this->supplier) ?: null, $this->paidFromRegister);
        } catch (BusinessRuleException $e) {
            $this->addError('delivery', $e->getMessage());

            return;
        }

        session()->flash('success', "Livraison enregistrée : {$result['quantity']} article(s) sur {$result['lines']} produit(s)"
            . ($result['total_cost'] > 0 ? ', ' . number_format($result['total_cost'], 0, ',', ' ') . ' FCFA' : '')
            . ($result['expense'] ? ' (dépense créée dans la caisse).' : '.'));

        $this->supplier = '';
        $this->paidFromRegister = false;
        $this->resetDeliveryLines();
        $this->tab = 'overview';
    }

    public function saveLoss(StockService $stock): void
    {
        $this->validate([
            'lossProductId' => 'required|integer',
            'lossQuantity' => 'required|integer|min:1|max:100000',
            'lossReason' => 'required|string|min:3|max:255',
        ], [
            'lossProductId.required' => 'Choisissez le produit.',
            'lossReason.required' => 'Indiquez le motif.',
            'lossReason.min' => 'Indiquez le motif.',
        ]);

        try {
            $movement = $stock->recordLoss(auth()->user(), $this->lossProductId, $this->lossQuantity, $this->lossReason);
        } catch (BusinessRuleException $e) {
            $this->addError('lossQuantity', $e->getMessage());

            return;
        }

        session()->flash('success', 'Perte enregistrée : ' . abs($movement->quantity) . ' × ' . $movement->product->name
            . ' (' . number_format(abs($movement->quantity) * $movement->unit_cost, 0, ',', ' ') . ' FCFA).');

        $this->reset(['lossProductId', 'lossReason']);
        $this->lossQuantity = 1;
        $this->tab = 'overview';
    }

    public function saveInventory(StockService $stock): void
    {
        $this->validate([
            'counts.*' => 'nullable|integer|min:0|max:1000000',
            'inventoryNote' => 'nullable|string|max:255',
        ], [
            'counts.*.integer' => 'Nombre entier attendu.',
            'counts.*.min' => 'Nombre invalide.',
        ]);

        try {
            $count = $stock->recordInventory(auth()->user(), $this->counts, trim($this->inventoryNote) ?: null);
        } catch (BusinessRuleException $e) {
            $this->addError('inventory', $e->getMessage());

            return;
        }

        $this->counts = [];
        $this->inventoryNote = '';
        $this->lastCountId = $count->id;
    }

    public function render()
    {
        $products = $this->products();

        return view('livewire.stock-manage', [
            'tabs' => self::TABS,
            'products' => $products,
            'categories' => Category::orderBy('sort_order')->get(),
            'stockValue' => $products->sum(fn (Product $p) => max(0, $p->stock_quantity) * $p->unitValue()),
            'missingCost' => $products->whereNull('cost_price')->count(),
            'lastCount' => $this->lastCountId
                ? StockCount::with(['movements.product', 'user'])->find($this->lastCountId)
                : null,
            'recentCounts' => StockCount::with('user')->latest()->limit(5)->get(),
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
