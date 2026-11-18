<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PaymentSuccess extends Component
{
    #[Locked]
    public int $orderId;

    public function mount($order)
    {
        // La commande doit appartenir au restaurant de l'utilisateur (sinon 404)
        $this->orderId = Order::findOrFail($order)->id;
    }

    public function render()
    {
        $order = Order::with(['items.product', 'restaurantTable', 'payment'])->findOrFail($this->orderId);

        return view('livewire.payment-success', [
            'order' => $order,
            'usesTables' => (bool) auth()->user()->currentRestaurant()?->usesTables(),
        ]);
    }
}