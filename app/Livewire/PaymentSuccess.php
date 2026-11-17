<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;

class PaymentSuccess extends Component
{
    public int $orderId;

    public function mount($order)
    {
        $this->orderId = $order;
    }

    public function render()
    {
        $order = Order::with(['items.product', 'restaurantTable', 'payment'])->findOrFail($this->orderId);

        return view('livewire.payment-success', ['order' => $order]);
    }
}