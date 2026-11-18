<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\CreditTransaction;
use App\Models\Customer;
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

        // Paiement avant service : la serveuse n'encaisse pas, elle apporte l'argent au comptoir
        if (auth()->user()->isServer() && auth()->user()->currentRestaurant()?->paysBeforeService()) {
            session()->flash('error', "C'est le comptoir qui encaisse.");
            $this->redirect(route('tables.index'), navigate: false);
        }
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
        if (auth()->user()->isServer() && auth()->user()->currentRestaurant()?->paysBeforeService()) {
            abort(403);
        }

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

    public ?int $creditCustomerId = null;

    protected function canCredit(): bool
    {
        return (bool) auth()->user()->currentRestaurant()?->creditEnabled()
            && CreditTransaction::canAuthorizeCredit(auth()->user());
    }

    /**
     * Le client ne paie pas maintenant : le bon va sur son ardoise.
     */
    public function chargeToCustomer()
    {
        abort_unless($this->canCredit(), 403);

        $this->validate(['creditCustomerId' => 'required|integer'], [
            'creditCustomerId.required' => 'Choisissez le client.',
        ]);

        $customer = Customer::find($this->creditCustomerId);

        if (! $customer) {
            $this->addError('creditCustomerId', 'Client introuvable.');

            return;
        }

        try {
            CreditTransaction::chargeOrder($this->order, $customer, auth()->user());
        } catch (BusinessRuleException $e) {
            $this->addError('creditCustomerId', $e->getMessage());

            return;
        }

        session()->flash('success', "{$this->order->order_number} mis sur l'ardoise de {$customer->name}.");

        $this->redirect(route('ardoise'), navigate: false);
    }

    public function render()
    {
        return view('livewire.take-payment', [
            'order' => $this->order,
            'canCredit' => $this->canCredit(),
            'customers' => $this->canCredit() ? Customer::orderBy('name')->get() : collect(),
        ]);
    }
}