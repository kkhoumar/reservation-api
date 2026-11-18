<?php

namespace App\Livewire;

use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Support\SalesSummary;
use Livewire\Component;

/**
 * Point de la journée : toutes les ventes réglées aujourd'hui, en espèces,
 * Mobile Money ou sur l'ardoise, avec les totaux par moyen de paiement.
 */
class SalesToday extends Component
{
    use \App\Livewire\Concerns\ShowsBusinessErrors;

    public function render()
    {
        $restaurantId = auth()->user()->currentRestaurantId();

        $payments = Payment::with(['order.restaurantTable', 'cashier'])
            ->where('status', 'completed')
            ->whereHas('order', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->where('paid_at', '>=', today())
            ->get()
            ->map(fn (Payment $p) => (object) [
                'at' => $p->paid_at,
                'order' => $p->order,
                'mode' => $p->methodLabel(),
                'detail' => $p->reference,
                'by' => $p->cashier?->first_name,
                'amount' => (int) $p->amount_due,
                'credit' => false,
            ]);

        $charges = CreditTransaction::with(['order.restaurantTable', 'recordedBy', 'customer'])
            ->where('type', 'charge')
            ->where('created_at', '>=', today())
            ->get()
            ->map(fn (CreditTransaction $t) => (object) [
                'at' => $t->created_at,
                'order' => $t->order,
                'mode' => 'Crédit',
                'detail' => $t->customer?->name,
                'by' => $t->recordedBy?->first_name,
                'amount' => (int) $t->amount,
                'credit' => true,
            ]);

        return view('livewire.sales-today', [
            'sales' => $payments->concat($charges)->sortByDesc('at')->values(),
            'summary' => new SalesSummary($restaurantId, today(), now()),
        ]);
    }
}
