<?php

namespace App\Livewire\Concerns;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Validation\Rule;

/**
 * Champs d'encaissement partagés (comptoir et écran d'encaissement) :
 * espèces, Mobile Money, ou Mobile Money + espèces pour le reste.
 *
 * Le composant fournit $amountReceived (espèces reçues) et paymentTotal().
 */
trait CollectsPayment
{
    public string $paymentMethod = 'cash';
    public ?int $mobileAmount = null;
    public string $reference = '';

    abstract protected function paymentTotal(): int;

    /** Choisir un opérateur : par défaut, tout est payé par Mobile Money. */
    public function updatedPaymentMethod(string $value): void
    {
        if ($value === 'cash') {
            $this->mobileAmount = null;
            $this->reference = '';
            $this->amountReceived = $this->paymentTotal();

            return;
        }

        if ($this->mobileAmount === null) {
            $this->mobileAmount = $this->paymentTotal();
            $this->amountReceived = 0;
        }
    }

    protected function resetPaymentFields(): void
    {
        $this->paymentMethod = 'cash';
        $this->mobileAmount = null;
        $this->reference = '';
    }

    protected function paymentRules(): array
    {
        return [
            'paymentMethod' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'mobileAmount' => $this->paymentMethod === 'cash' ? 'nullable' : 'required|integer|min:1|max:100000000',
            'reference' => 'nullable|string|max:100',
            'amountReceived' => 'nullable|integer|min:0|max:100000000',
        ];
    }

    protected function paymentMessages(): array
    {
        return [
            'mobileAmount.required' => 'Indiquez le montant payé par Mobile Money.',
            'mobileAmount.min' => 'Le montant Mobile Money doit être supérieur à zéro.',
        ];
    }

    protected function mobilePart(): int
    {
        return $this->paymentMethod === 'cash' ? 0 : (int) $this->mobileAmount;
    }

    /** Espèces encore à recevoir après la part Mobile Money. */
    public function cashDue(): int
    {
        return max(0, $this->paymentTotal() - $this->mobilePart());
    }

    public function changeToGive(): int
    {
        return max(0, (int) $this->amountReceived - $this->cashDue());
    }

    /**
     * @throws \App\Exceptions\BusinessRuleException
     */
    protected function recordPaymentFor(Order $order): Payment
    {
        return Payment::recordPayment(
            $order,
            auth()->user(),
            (int) $this->amountReceived,
            $this->paymentMethod === 'cash' ? null : $this->paymentMethod,
            $this->mobilePart(),
            trim($this->reference) ?: null,
        );
    }
}
