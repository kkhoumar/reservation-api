<?php

namespace App\Livewire;

use App\Models\Payment;
use Livewire\Component;

/**
 * Vue "point de la journée" pour le caissier (PRD section 21, Rapport des ventes,
 * simplifié). Liste tous les paiements encaissés aujourd'hui avec total cumulé.
 * Complète l'écran Caisse : consultable à tout moment sans devoir clôturer.
 */
class SalesToday extends Component
{
    public function render()
    {
        $payments = Payment::with(['order.restaurantTable', 'cashier'])
            ->where('status', 'completed')
            ->whereHas('order', fn ($q) => $q->where('restaurant_id', auth()->user()->restaurant_id))
            ->whereDate('paid_at', now()->toDateString())
            ->latest('paid_at')
            ->get();

        return view('livewire.sales-today', [
            'payments' => $payments,
            'total' => $payments->sum(fn ($p) => $p->order->total),
        ]);
    }
}