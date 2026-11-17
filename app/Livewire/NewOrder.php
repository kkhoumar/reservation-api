<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Nouvelle commande (PRD section 9-10).
 * Parcours pensé pour être rapide :
 * sélection catégorie -> produit -> quantité -> valider.
 *
 * Une commande "brouillon" est créée dès l'ajout du premier article,
 * pour que rien ne soit perdu si le serveur est interrompu.
 * Elle passe au statut "in_preparation" seulement quand le serveur
 * clique sur "Envoyer en cuisine".
 */
class NewOrder extends Component
{
    // Verrouillés : le navigateur ne peut pas les modifier
    #[Locked]
    public ?int $tableId = null;
    #[Locked]
    public ?int $orderId = null;
    #[Locked]
    public string $orderType = 'dine_in'; // dine_in | takeaway

    public ?int $selectedCategoryId = null;
    public int $quantity = 1;
    public string $note = '';

    public function mount($table = null)
    {
        // La table doit appartenir au restaurant de l'utilisateur (sinon 404)
        $this->tableId = $table ? RestaurantTable::findOrFail($table)->id : null;

        if (!$this->tableId) {
            $this->orderType = 'takeaway';
        }

        // Reprend une commande déjà ouverte sur cette table
        if ($this->tableId) {
            $existing = Order::where('table_id', $this->tableId)
                ->whereIn('status', ['created', 'in_preparation'])
                ->first();

            if ($existing) {
                $this->orderId = $existing->id;
            }
        }
    }

    public function getOrderProperty()
    {
        return $this->orderId
            ? Order::with('items.product')->find($this->orderId)
            : null;
    }

    public function getCategoriesProperty()
    {
        return Category::where(
            'restaurant_id',
            auth()->user()->restaurant_id
        )
            ->with([
                'products' => fn ($q) =>
                    $q->where('is_available', true)
            ])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Ajoute un produit à la commande.
     *
     * Protection côté serveur :
     * - vérifie que l'utilisateur est connecté ;
     * - vérifie que le produit existe ;
     * - vérifie que le produit est disponible en stock ;
     * - vérifie que le stock est suffisant pour la quantité demandée ;
     * - décrémente le stock après création de la ligne de commande.
     */
    public function addProduct($productId)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $userId = auth()->id();

        // Vérification de l'utilisateur
        if (!$restaurantId || !$userId) {
            session()->flash(
                'error',
                'Connecte-toi avec un utilisateur avant de prendre une commande.'
            );

            return;
        }

        // Vérifie que le produit existe
        $product = \App\Models\Product::findOrFail($productId);

        // Vérifie que le produit est disponible
        if (!$product->is_available) {
            session()->flash(
                'error',
                "Le produit {$product->name} n'est plus disponible."
            );

            return;
        }

        // Vérifie que le stock n'est pas à zéro
        if ($product->stock_quantity <= 0) {
            session()->flash(
                'error',
                "Rupture de stock : {$product->name} n'est plus disponible."
            );

            return;
        }

        // Vérifie que le stock est suffisant pour la quantité demandée
        if ($product->stock_quantity < $this->quantity) {
            session()->flash(
                'error',
                "Stock insuffisant : {$product->name}. "
                . "Stock disponible : {$product->stock_quantity}."
            );

            return;
        }

        // Crée la commande au premier article ajouté
        if (!$this->orderId) {
            $order = Order::create([
                'restaurant_id' => $restaurantId,
                'table_id' => $this->tableId,
                'user_id' => $userId,
                'order_number' => Order::generateOrderNumber(),
                'order_type' => $this->orderType,
                'status' => 'created',
            ]);

            $this->orderId = $order->id;

            // Occupe la table
            if ($this->tableId) {
                RestaurantTable::find($this->tableId)?->update([
                    'status' => 'occupied'
                ]);
            }
        }

        // Crée la ligne de commande
        OrderItem::create([
            'order_id' => $this->orderId,
            'product_id' => $product->id,
            'quantity' => $this->quantity,
            'unit_price' => $product->selling_price,
            'total' => $product->selling_price * $this->quantity,
            'note' => $this->note ?: null,
        ]);

        // Décrémente le stock
        $product->decrementStock($this->quantity);

        // Recalcule le total de la commande
        $this->order->recalculateTotal();

        // Réinitialise la quantité et la note
        $this->quantity = 1;
        $this->note = '';
    }

    /**
     * Supprime un article de la commande.
     */
    public function removeItem($itemId)
    {
        // Uniquement une ligne de LA commande en cours (et donc de ce restaurant)
        if (!$this->orderId) {
            return;
        }

        OrderItem::where('order_id', $this->orderId)->find($itemId)?->delete();

        $this->order?->recalculateTotal();
    }

    /**
     * Envoie la commande en cuisine.
     *
     * Workflow :
     * Créée -> En préparation -> Servie -> Paiement
     */
    public function sendToKitchen()
    {
        $this->order?->update([
            'status' => 'in_preparation'
        ]);

        session()->flash(
            'success',
            'Commande envoyée en cuisine.'
        );
    }

    /**
     * Marque la commande comme servie.
     */
    public function markServed()
    {
        $this->order?->update([
            'status' => 'served'
        ]);
    }

    /**
     * Passe la commande au paiement.
     */
    public function goToPayment()
    {
        $this->order?->update([
            'status' => 'awaiting_payment'
        ]);

        if ($this->tableId) {
            RestaurantTable::find($this->tableId)?->update([
                'status' => 'awaiting_payment'
            ]);
        }

        return redirect()->route(
            'payment.take',
            ['order' => $this->orderId]
        );
    }

    /**
     * Affiche la page de nouvelle commande.
     */
    public function render()
    {
        return view('livewire.new-order', [
            'categories' => $this->categories,
            'order' => $this->order,
        ]);
    }
}