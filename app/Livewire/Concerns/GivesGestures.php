<?php

namespace App\Livewire\Concerns;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;

/**
 * Gestes commerciaux (patron et gérante) : offrir un article, faire une remise.
 * Partagé par le comptoir et l'écran d'encaissement.
 */
trait GivesGestures
{
    public ?int $offerItemId = null;
    public ?int $offerQuantity = 1;
    public string $offerReason = '';
    public ?int $discountInput = null;
    public string $discountReason = '';

    /** Le bon sur lequel portent les gestes (null = aucun). */
    abstract protected function gestureOrder(): ?Order;

    /** Appelé après chaque geste (par exemple pour mettre à jour le montant à encaisser). */
    protected function afterGesture(Order $order): void
    {
    }

    protected function canGiveGestures(): bool
    {
        return Order::canGiveGestures(auth()->user());
    }

    protected function resetGestureFields(): void
    {
        $this->offerItemId = null;
        $this->offerQuantity = 1;
        $this->offerReason = '';
        $this->discountInput = null;
        $this->discountReason = '';
    }

    public function selectOfferItem(int $itemId): void
    {
        abort_unless($this->canGiveGestures(), 403);

        $this->offerItemId = $itemId;
        $this->offerQuantity = 1;
        $this->offerReason = '';
        $this->resetValidation();
    }

    public function confirmOffer(): void
    {
        abort_unless($this->canGiveGestures(), 403);

        $order = $this->gestureOrder();

        if (! $order || ! $this->offerItemId) {
            return;
        }

        $this->validate([
            'offerQuantity' => 'required|integer|min:1|max:1000',
            'offerReason' => 'required|string|min:3|max:255',
        ], [
            'offerReason.required' => 'Indiquez le motif.',
            'offerReason.min' => 'Indiquez le motif.',
        ]);

        try {
            $order->offerItem($this->offerItemId, $this->offerQuantity, auth()->user(), $this->offerReason);
        } catch (BusinessRuleException $e) {
            $this->addError('offerReason', $e->getMessage());

            return;
        }

        $this->offerItemId = null;
        $this->offerReason = '';
        $this->afterGesture($order->refresh());
    }

    public function cancelOffer(int $itemId): void
    {
        abort_unless($this->canGiveGestures(), 403);

        if (! $order = $this->gestureOrder()) {
            return;
        }

        try {
            $order->cancelOffer($itemId, auth()->user());
        } catch (BusinessRuleException $e) {
            $this->addError('offerReason', $e->getMessage());

            return;
        }

        $this->afterGesture($order->refresh());
    }

    public function applyDiscount(): void
    {
        abort_unless($this->canGiveGestures(), 403);

        if (! $order = $this->gestureOrder()) {
            return;
        }

        $this->validate([
            'discountInput' => 'required|integer|min:1|max:100000000',
            'discountReason' => 'required|string|min:3|max:255',
        ], [
            'discountInput.required' => 'Indiquez le montant de la remise.',
            'discountReason.required' => 'Indiquez le motif.',
            'discountReason.min' => 'Indiquez le motif.',
        ]);

        try {
            $order->applyDiscount($this->discountInput, auth()->user(), $this->discountReason);
        } catch (BusinessRuleException $e) {
            $this->addError('discountInput', $e->getMessage());

            return;
        }

        $this->discountInput = null;
        $this->discountReason = '';
        $this->afterGesture($order->refresh());
    }

    public function removeDiscount(): void
    {
        abort_unless($this->canGiveGestures(), 403);

        if (! $order = $this->gestureOrder()) {
            return;
        }

        try {
            $order->applyDiscount(0, auth()->user(), '');
        } catch (BusinessRuleException $e) {
            $this->addError('discountInput', $e->getMessage());

            return;
        }

        $this->afterGesture($order->refresh());
    }
}
