<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Le comptoir (paiement avant service).
 *
 * Les serveuses envoient leurs bons ; le comptoir encaisse l'argent
 * qu'elles apportent, puis remet les boissons et les plats.
 * Pas d'argent, pas de service.
 */
class Counter extends Component
{
    #[Locked]
    public ?int $orderId = null;

    public ?int $amountReceived = null;

    public function mount(): void
    {
        if (! auth()->user()->currentRestaurant()?->paysBeforeService()) {
            session()->flash('error', 'Ce maquis encaisse à la fin du repas : utilisez le plan des tables.');
            $this->redirect(route('tables.index'), navigate: false);
        }
    }

    protected function findPendingOrder(int $id): Order
    {
        return Order::where('status', 'awaiting_payment')->findOrFail($id);
    }

    public function startPayment(int $id): void
    {
        $order = $this->findPendingOrder($id);

        $this->orderId = $order->id;
        $this->amountReceived = $order->total;
        $this->resetValidation();
    }

    public function cancelPayment(): void
    {
        $this->reset(['orderId', 'amountReceived']);
        $this->resetValidation();
    }

    /**
     * « Encaissé, servir » : l'argent entre en caisse, le bon peut être servi.
     */
    public function confirmPayment(): void
    {
        if ($this->orderId === null) {
            return;
        }

        $this->validate([
            'amountReceived' => 'required|integer|min:0|max:100000000',
        ]);

        try {
            $order = $this->findPendingOrder($this->orderId);
            Payment::recordCashPayment($order, auth()->user(), $this->amountReceived);
        } catch (BusinessRuleException $e) {
            $this->addError('amountReceived', $e->getMessage());

            return;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Encaissé ou annulé entre-temps par quelqu'un d'autre
            $this->cancelPayment();
            session()->flash('error', 'Ce bon a déjà été traité.');

            return;
        }

        $change = $this->amountReceived - $order->total;
        session()->flash('success', "{$order->order_number} encaissé : servez la commande."
            . ($change > 0 ? ' Monnaie à rendre : ' . number_format($change, 0, ',', ' ') . ' FCFA.' : ''));

        $this->cancelPayment();
    }

    /**
     * Le client renonce : le bon est annulé et les articles reviennent en stock.
     */
    public function cancelOrder(int $id): void
    {
        $order = $this->findPendingOrder($id);

        try {
            $order->cancel();
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        Log::info('Bon annulé au comptoir', [
            'user_id' => auth()->id(),
            'order_id' => $order->id,
            'total' => $order->total,
        ]);

        if ($this->orderId === $order->id) {
            $this->cancelPayment();
        }

        session()->flash('success', "{$order->order_number} annulé : les articles sont remis en stock.");
    }

    /**
     * Les clients sont partis : la table redevient libre,
     * seulement si tous ses bons sont réglés.
     */
    public function releaseTable(int $id): void
    {
        $released = DB::transaction(function () use ($id) {
            $table = RestaurantTable::whereKey($id)->lockForUpdate()->firstOrFail();

            $open = Order::where('table_id', $table->id)
                ->whereIn('status', Order::OPEN_STATUSES)
                ->with('items')
                ->get();

            // Un bon commencé puis vidé ne bloque pas la table
            $open->filter(fn (Order $o) => $o->items->isEmpty())->each->cancel();

            if ($open->contains(fn (Order $o) => $o->items->isNotEmpty())) {
                return false;
            }

            $table->update(['status' => 'free']);

            return true;
        });

        session()->flash($released ? 'success' : 'error', $released
            ? 'Table libérée.'
            : 'Cette table a encore un bon non réglé : encaissez-le ou annulez-le d\'abord.');
    }

    public function render()
    {
        $restaurantId = auth()->user()->currentRestaurantId();

        $pending = Order::with(['items.product', 'restaurantTable', 'server'])
            ->where('status', 'awaiting_payment')
            ->oldest('updated_at')
            ->get();

        $tablesToRelease = RestaurantTable::where('restaurant_id', $restaurantId)
            ->where('status', '!=', 'free')
            ->whereDoesntHave('orders', fn ($q) => $q->whereIn('status', Order::OPEN_STATUSES)->whereHas('items'))
            ->orderBy('label')
            ->get();

        // Ce que chaque serveuse a apporté aujourd'hui
        $byServer = Order::with('server')
            ->where('status', 'paid')
            ->whereHas('payment', fn ($q) => $q->where('status', 'completed')->where('paid_at', '>=', today()))
            ->selectRaw('user_id, count(*) as bons, sum(total) as amount')
            ->groupBy('user_id')
            ->orderByDesc('amount')
            ->get();

        return view('livewire.counter', [
            'pending' => $pending,
            'selected' => $this->orderId ? $pending->firstWhere('id', $this->orderId) : null,
            'tablesToRelease' => $tablesToRelease,
            'byServer' => $byServer,
            'registerOpen' => CashRegister::where('restaurant_id', $restaurantId)->whereNull('closed_at')->exists(),
        ]);
    }
}
