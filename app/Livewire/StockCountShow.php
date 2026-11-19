<?php

namespace App\Livewire;

use App\Actions\StockService;
use App\Models\StockCount;
use App\Support\StockCountReport;
use Livewire\Component;

/**
 * Résultat d'un comptage : écarts, totaux boissons / nourriture, bénéfice
 * possible, et envoi du résumé sur WhatsApp.
 */
class StockCountShow extends Component
{
    public StockCount $stockCount;

    public function mount(StockCount $stockCount): void
    {
        abort_unless(StockService::canManage(auth()->user()), 403);
        $this->stockCount = $stockCount;
    }

    public function render()
    {
        $report = new StockCountReport($this->stockCount);

        return view('livewire.stock-count-show', [
            'count' => $this->stockCount,
            'report' => $report,
            'summary' => $report->summary(),
            'ownerUrl' => $report->whatsappToOwnerUrl(),
            'shareUrl' => $report->whatsappShareUrl(),
            'next' => StockCount::where('created_at', '>', $this->stockCount->created_at)->oldest('created_at')->first(),
        ]);
    }
}
