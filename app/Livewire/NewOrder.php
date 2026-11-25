<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
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

        // Le mode de service du maquis décide des parcours autorisés
        $restaurant = auth()->user()->currentRestaurant();

        if ($this->tableId && ! $restaurant?->usesTables()) {
            $this->redirect(route('orders.new'), navigate: false);

            return;
        }

        if (! $this->tableId && ! $restaurant?->usesQuickSale()) {
            session()->flash('error', "La vente rapide n'est pas activée dans ce maquis. Choisissez une table.");
            $this->redirect(route('tables.index'), navigate: false);

            return;
        }

        if (!$this->tableId) {
            $this->orderType = 'takeaway';
        }

        // Reprend une commande déjà ouverte sur cette table
        if ($this->tableId) {
            $existing = Order::where('table_id', $this->tableId)
                ->whereIn('status', Order::EDITABLE_STATUSES)
                ->first();

            if ($existing) {
                $this->orderId = $existing->id;
            }
        }
    }

    public function getOrderProperty()
    {
        return $this->orderId
            ? Order::with(['items' => fn ($q) => $q->orderBy('id'), 'items.product'])->find($this->orderId)
            : null;
    }

    public function getCategoriesProperty()
    {
        return Category::where(
            'restaurant_id',
            auth()->user()->currentRestaurantId()
        )
            ->with([
                'products' => fn ($q) =>
                    $q->where('is_available', true)->whereNull('archived_at')->orderBy('name')
            ])
            ->orderBy('sort_order')->orderBy('id')
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
    public function addProduct($productId, $count = null, $tapId = null)
    {
        // Touches regroupées par le téléphone (« 3 × Flag » en un seul envoi).
        // $tapId rend chaque envoi unique : deux envois identiques ne sont jamais fusionnés.
        if ($count !== null) {
            $this->quantity = (int) $count;
            $this->note = '';
        }

        $this->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'note' => 'nullable|string|max:255',
        ], [
            'quantity.min' => 'La quantité doit être d\'au moins 1.',
            'quantity.max' => 'La quantité ne peut pas dépasser 100.',
        ]);

        try {
            // Tout ou rien : commande + ligne + stock + total
            $orderId = DB::transaction(function () use ($productId) {
                // Produit verrouillé : deux serveuses ne peuvent pas vendre
                // en même temps la dernière bouteille
                $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

                if (!$product->is_available) {
                    throw new BusinessRuleException("Le produit {$product->name} n'est plus disponible.");
                }

                if ($reason = $product->cannotSellReason($this->quantity)) {
                    throw new BusinessRuleException($reason);
                }

                $order = $this->orderId
                    ? $this->lockEditableOrder()
                    : $this->createOrder();

                // Même produit, pas encore envoyé, même prix, sans remarque ni cadeau :
                // les touches successives grossissent la même ligne (3 × Flag, pas 3 lignes)
                $line = $this->note === ''
                    ? OrderItem::where('order_id', $order->id)
                        ->where('product_id', $product->id)
                        ->whereNull('sent_at')
                        ->whereNull('note')
                        ->where('is_offered', false)
                        ->where('unit_price', $product->selling_price)
                        ->first()
                    : null;

                if ($line) {
                    $line->quantity += $this->quantity;
                    $line->total = $line->unit_price * $line->quantity;
                    $line->save();

                    $product->decrementStock($this->quantity);
                    $order->recalculateTotal();

                    return $order->id;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $this->quantity,
                    // Le prix vient toujours de la base, jamais du navigateur
                    'unit_price' => $product->selling_price,
                    // Prix d'achat figé à la vente : le bénéfice passé ne change pas si le prix change
                    'unit_cost' => $product->cost_price,
                    'total' => $product->selling_price * $this->quantity,
                    'note' => $this->note ?: null,
                ]);

                $product->decrementStock($this->quantity);
                $order->recalculateTotal();

                return $order->id;
            });
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        // Affecté seulement une fois la transaction validée
        $this->orderId = $orderId;
        $this->quantity = 1;
        $this->note = '';
    }

    /**
     * Retire un article de la commande et remet sa quantité en stock.
     */
    public function removeItem($itemId)
    {
        $this->takeOff($itemId, null);
    }

    /**
     * Bouton « − » : un article de moins sur la ligne (à 0, la ligne disparaît).
     */
    public function decrementItem($itemId, $count = 1, $tapId = null)
    {
        $this->takeOff($itemId, max(1, min(100, (int) $count)));
    }

    /**
     * Bouton « + » d'une ligne : un article de plus du même produit.
     */
    public function incrementItem($itemId)
    {
        if (!$this->orderId) {
            return;
        }

        $productId = OrderItem::where('order_id', $this->orderId)->whereKey($itemId)->value('product_id');

        if ($productId) {
            $this->quantity = 1;
            $this->note = '';
            $this->addProduct($productId);
        }
    }

    /**
     * Retire des articles d'une ligne de LA commande en cours et les remet en stock.
     * $count null = toute la ligne. Un article déjà parti en cuisine ne peut
     * être retiré que par le comptoir ou la direction, et c'est noté au journal.
     */
    protected function takeOff($itemId, ?int $count): void
    {
        // Uniquement une ligne de LA commande en cours (et donc de ce restaurant)
        if (!$this->orderId) {
            return;
        }

        try {
            DB::transaction(function () use ($itemId, $count) {
                $order = $this->lockEditableOrder();

                $item = OrderItem::where('order_id', $order->id)->lockForUpdate()->find($itemId);

                if (!$item) {
                    return;
                }

                $sent = $item->isSent();

                if ($item->isSent() && auth()->user()->isServer()) {
                    throw new BusinessRuleException("{$item->product?->name} est déjà parti en cuisine : demandez au comptoir pour le retirer.");
                }

                $removed = min($count ?? $item->quantity, $item->quantity);
                $unit = intdiv((int) $item->total, max(1, (int) $item->quantity));

                Product::restoreStock($item->product_id, $removed);

                if ($removed >= $item->quantity) {
                    $item->delete();
                } else {
                    $item->quantity -= $removed;
                    $item->total = $unit * $item->quantity;
                    $item->save();
                }

                $order->recalculateTotal();

                // Retrait après l'envoi en cuisine : à surveiller. Avant l'envoi, ce n'est
                // qu'une correction de saisie (un « − » de trop) : pas de bruit au journal.
                if ($sent) {
                    \App\Models\ActivityLog::record('item_removed', 'a retiré ' . $removed . ' × ' . ($item->product?->name ?? 'article') . " du bon {$order->order_number} après l'envoi en cuisine.", [
                        'amount' => -($unit * $removed), 'meta' => ['order_id' => $order->id, 'sent' => true],
                    ]);
                }
            });
        } catch (BusinessRuleException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Envoie la commande en cuisine.
     *
     * Workflow :
     * Créée -> En préparation -> Servie -> Paiement
     */
    public function sendToKitchen()
    {
        if (!$this->changeStatus('in_preparation')) {
            return;
        }

        // Les lignes parties en cuisine sont figées pour les serveurs
        OrderItem::where('order_id', $this->orderId)->whereNull('sent_at')->update(['sent_at' => now()]);

        session()->flash('success', 'Commande envoyée en cuisine.');
    }

    /**
     * Marque la commande comme servie.
     */
    public function markServed()
    {
        $this->changeStatus('served');
    }

    /**
     * Passe la commande au paiement.
     */
    public function goToPayment()
    {
        $paysBefore = $this->paysBeforeService();

        // Paiement avant service : seul le comptoir encaisse
        if ($paysBefore && auth()->user()->isServer()) {
            session()->flash('error', "C'est le comptoir qui encaisse : envoie le bon au comptoir.");

            return;
        }

        if (!$this->changeStatus('awaiting_payment')) {
            return;
        }

        if ($this->tableId && ! $paysBefore) {
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
     * Paiement avant service : la serveuse envoie le bon au comptoir.
     * Le comptoir encaisse l'argent qu'elle apporte, puis on sert.
     * La table reste occupée : la tournée suivante fera un nouveau bon.
     */
    public function sendToCounter()
    {
        if (! $this->paysBeforeService()) {
            return;
        }

        if (!$this->changeStatus('awaiting_payment')) {
            return;
        }

        $order = $this->order;
        session()->flash('success', "Bon {$order->order_number} envoyé au comptoir : "
            . number_format($order->total, 0, ',', ' ') . ' FCFA à encaisser.');

        return $this->tableId
            ? redirect()->route('tables.index')
            : redirect()->route('orders.new');
    }

    protected function paysBeforeService(): bool
    {
        return (bool) auth()->user()->currentRestaurant()?->paysBeforeService();
    }

    /**
     * Fait avancer la commande, seulement si elle est encore modifiable
     * et contient au moins un article.
     */
    protected function changeStatus(string $status): bool
    {
        $order = $this->order;

        if (!$order || $order->items->isEmpty()) {
            session()->flash('error', 'Ajoutez au moins un article avant de continuer.');

            return false;
        }

        if (!$order->isEditable()) {
            session()->flash('error', 'Cette commande est déjà à l\'encaissement ou payée.');

            return false;
        }

        $order->update(['status' => $status]);

        return true;
    }

    /**
     * Commande en cours, verrouillée, et encore modifiable.
     *
     * @throws BusinessRuleException
     */
    protected function lockEditableOrder(): Order
    {
        $order = Order::whereKey($this->orderId)->lockForUpdate()->firstOrFail();

        if (!$order->isEditable()) {
            throw new BusinessRuleException('Cette commande est déjà à l\'encaissement ou payée : elle ne peut plus être modifiée.');
        }

        return $order;
    }

    protected function createOrder(): Order
    {
        $order = Order::create([
            'restaurant_id' => auth()->user()->currentRestaurantId(),
            'table_id' => $this->tableId,
            'user_id' => auth()->id(),
            'order_type' => $this->orderType,
            'status' => 'created',
        ]);

        if ($this->tableId) {
            RestaurantTable::find($this->tableId)?->update(['status' => 'occupied']);
        }

        return $order;
    }

    /**
     * Affiche la page de nouvelle commande.
     */
    public function render()
    {
        $order = $this->order;

        return view('livewire.new-order', [
            'categories' => $this->categories,
            'order' => $order,
            // Nombre de chaque produit déjà dans la commande (pastille sur la carte)
            'inOrder' => $order ? $order->items->groupBy('product_id')->map->sum('quantity')->all() : [],
            // Un serveur ne diminue pas un article parti en cuisine
            'canTrimSent' => ! auth()->user()->isServer(),
            'paysBefore' => $this->paysBeforeService(),
            'canCollect' => ! auth()->user()->isServer(),
            'hasServers' => (bool) auth()->user()->currentRestaurant()?->hasActiveServers(),
        ]);
    }
}