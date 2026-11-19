<?php

namespace App\Livewire;

use App\Actions\StockService;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\StockCount;
use App\Support\ReadOnlyGuard;
use App\Support\StockCountSheet;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Comptage du stock (patron et gérante) : l'application montre ce qui doit
 * rester depuis le dernier comptage, le patron tape ce qu'il voit, explique
 * les écarts et valide. Les calculs se font dans le téléphone, sans attendre.
 */
class StockCountPage extends Component
{
    public function mount(): void
    {
        abort_unless(StockService::canManage(auth()->user()), 403);
    }

    /**
     * Appelé par le bouton « Valider le comptage ».
     *
     * @return string|null  message d'erreur à afficher, null si tout va bien
     */
    #[Renderless]
    public function save(array $counts, array $reasons = [], string $note = ''): ?string
    {
        $counts = collect($counts)
            ->filter(fn ($v, $k) => is_numeric($k))
            ->map(fn ($v) => is_int($v) ? $v : (is_string($v) ? trim($v) : null))
            ->all();

        $reasons = collect($reasons)
            ->filter(fn ($v, $k) => is_numeric($k) && is_string($v))
            ->all();

        try {
            $count = app(StockService::class)->recordInventory(auth()->user(), $counts, mb_substr(trim($note), 0, 255) ?: null, $reasons);
        } catch (BusinessRuleException $e) {
            return $e->getMessage();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 'Un produit a été supprimé pendant le comptage. Rechargez la page.';
        }

        session()->flash('success', 'Comptage validé. Le stock de l\'application est maintenant celui que vous avez compté.');
        $this->redirectRoute('stock.count.show', ['stockCount' => $count->id]);

        return null;
    }

    public function render()
    {
        $sheet = new StockCountSheet(auth()->user()->currentRestaurantId());
        $rows = $sheet->rows();

        return view('livewire.stock-count-page', [
            'sheet' => $sheet,
            'rows' => $rows,
            'registers' => $sheet->registers(),
            'history' => StockCount::with('user')->latest('created_at')->latest('id')->limit(10)->get(),
            'missingCost' => $rows->whereNull('cost')->count(),
            'readOnly' => ReadOnlyGuard::isReadOnly(),
            'hasProducts' => Product::exists(),
        ]);
    }
}
