<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Encaissement (PRD section 12) — ESPÈCES UNIQUEMENT au MVP.
 * Le caissier saisit le montant reçu, le système calcule la monnaie
 * et utilise Payment::recordCashPayment() qui marque aussi la commande
 * PAYÉE et libère la table (cf critère d'acceptation "Paiement" du PRD).
 */
class TakePayment extends Component
{
    #[Locked]
    public int $orderId;
    public int $amountReceived = 0;

    public function mount($order)
    {
        // La commande doit appartenir au restaurant de l'utilisateur (sinon 404)
        $this->orderId = Order::findOrFail($order)->id;
    }

    public function getOrderProperty()
    {
        return Order::with(['items.product', 'restaurantTable'])->findOrFail($this->orderId);
    }

    public function getChangeProperty()
    {
        return max(0, $this->amountReceived - $this->order->total);
    }

    public function confirmPayment()
    {
        $this->validate([
            'amountReceived' => 'required|integer|min:0|max:100000000',
        ]);

        try {
            Payment::recordCashPayment($this->order, auth()->user(), $this->amountReceived);
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->redirect(route('payment.success', ['order' => $this->orderId]), navigate: false);
    }

    public function render()
    {
        return view('livewire.take-payment', ['order' => $this->order]);
    }
}