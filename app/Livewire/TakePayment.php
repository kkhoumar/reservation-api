<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Livewire\Concerns\CollectsPayment;
use App\Livewire\Concerns\GivesGestures;
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
    use CollectsPayment, GivesGestures;

    public bool $showGestures = false;

    protected function gestureOrder(): ?Order
    {
        $order = Order::find($this->orderId);

        return $order && ! $order->isPaid() && $order->status !== 'cancelled' ? $order : null;
    }

    /** Le total a changé : on remet le montant à encaisser à jour. */
    protected function afterGesture(Order $order): void
    {
        $this->resetPaymentFields();
        $this->amountReceived = $order->total;
    }

    #[Locked]
    public int $orderId;
    public ?int $amountReceived = null;

    public function mount($order)
    {
        // La commande doit appartenir au restaurant de l'utilisateur (sinon 404)
        $found = Order::findOrFail($order);
        $this->orderId = $found->id;
        $this->amountReceived = $found->total;

        // Paiement avant service : la serveuse n'encaisse pas, elle apporte l'argent au comptoir
        if (auth()->user()->isServer() && auth()->user()->currentRestaurant()?->paysBeforeService()) {
            session()->flash('error', "C'est le comptoir qui encaisse.");
            $this->redirect(route('tables.index'), navigate: false);
        }
    }

    public function getOrderProperty()
    {
        return Order::with(['items.product', 'items.offeredBy', 'discountedBy', 'restaurantTable'])->findOrFail($this->orderId);
    }

    protected function paymentTotal(): int
    {
        return (int) Order::whereKey($this->orderId)->value('total');
    }

    public function confirmPayment()
    {
        if (auth()->user()->isServer() && auth()->user()->currentRestaurant()?->paysBeforeService()) {
            abort(403);
        }

        $this->validate($this->paymentRules(), $this->paymentMessages());

        try {
            $this->recordPaymentFor($this->order);
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
            $this->addError('creditCustomerId', 'Ce client n\'existe plus. Rechargez la page et choisissez-le de nouveau.');

            return;
        }

        try {
            CreditTransaction::chargeOrder($this->order, $customer, auth()->user());
        } catch (BusinessRuleException $e) {
            $this->addError('creditCustomerId', $e->getMessage());

            return;
        }

        session()->flash('success', "{$this->order->order_number} mis à crédit pour {$customer->name}.");

        $this->redirect(route('credits'), navigate: false);
    }

    public function render()
    {
        return view('livewire.take-payment', [
            'order' => $this->order,
            'canCredit' => $this->canCredit(),
            'canGestures' => $this->canGiveGestures(),
            'registerOpen' => $this->registerIsOpen(),
            'customers' => $this->canCredit() ? Customer::orderBy('name')->get() : collect(),
        ]);
    }
}