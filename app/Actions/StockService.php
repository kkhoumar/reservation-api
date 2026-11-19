<?php

namespace App\Actions;

use App\Exceptions\BusinessRuleException;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Le stock ne bouge que par une vente, une livraison, une perte ou un
 * inventaire, et chaque mouvement est tracé (qui, quand, combien, pourquoi).
 * Réservé au patron et à la gérante.
 */
class StockService
{
    public static function canManage(User $user): bool
    {
        return $user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager'], true);
    }

    /**
     * Livraison (bon de livraison) : plusieurs produits d'un coup.
     *
     * @param  array<int, array{quantity: int, unit_cost: ?int}>  $lines  par id de produit
     * @param  bool  $paidFromRegister  payée avec l'argent de la caisse : crée la dépense
     * @return array{lines: int, quantity: int, total_cost: int, expense: ?Expense}
     *
     * @throws BusinessRuleException
     */
    public function recordDelivery(User $by, array $lines, ?string $supplier = null, bool $paidFromRegister = false): array
    {
        $this->guard($by);

        $lines = array_filter($lines, fn ($l) => (int) ($l['quantity'] ?? 0) !== 0);

        if ($lines === []) {
            throw new BusinessRuleException('Indiquez au moins un produit livré et sa quantité.');
        }

        return DB::transaction(function () use ($by, $lines, $supplier, $paidFromRegister) {
            $restaurantId = $by->currentRestaurantId();
            $supplier = $supplier ? mb_substr(trim($supplier), 0, 120) : null;
            $movements = [];
            $totalCost = 0;
            $totalQty = 0;

            foreach ($lines as $productId => $line) {
                $quantity = (int) $line['quantity'];
                $unitCost = isset($line['unit_cost']) && $line['unit_cost'] !== '' && $line['unit_cost'] !== null ? (int) $line['unit_cost'] : null;

                if ($quantity < 1 || $quantity > 100000) {
                    throw new BusinessRuleException('Entrez une quantité livrée entre 1 et 100 000.');
                }

                if ($unitCost !== null && ($unitCost < 0 || $unitCost > 10000000)) {
                    throw new BusinessRuleException('Prix d\'achat invalide.');
                }

                $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                $before = $product->stock_quantity;

                $product->stock_quantity = $before + $quantity;
                if ($unitCost !== null) {
                    $product->cost_price = $unitCost; // dernier prix d'achat connu
                }
                $product->save();

                $movements[] = StockMovement::create([
                    'restaurant_id' => $restaurantId,
                    'product_id' => $product->id,
                    'user_id' => $by->id,
                    'type' => 'delivery',
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $product->stock_quantity,
                    'unit_cost' => $unitCost,
                    'supplier' => $supplier,
                ]);

                $totalCost += $quantity * ($unitCost ?? 0);
                $totalQty += $quantity;
            }

            $expense = null;

            if ($paidFromRegister) {
                if ($totalCost <= 0) {
                    throw new BusinessRuleException('Indiquez les prix d\'achat pour créer la dépense.');
                }

                $register = CashRegister::where('restaurant_id', $restaurantId)->whereNull('closed_at')->latest('opened_at')->first();

                if (! $register) {
                    throw new BusinessRuleException('Ouvrez la caisse pour payer la livraison avec son argent, ou décochez « payée avec la caisse ».');
                }

                $expense = Expense::create([
                    'restaurant_id' => $restaurantId,
                    'user_id' => $by->id,
                    'cash_register_id' => $register->id,
                    'category' => 'goods_purchase',
                    'amount' => $totalCost,
                    'description' => 'Livraison' . ($supplier ? " {$supplier}" : '') . " ({$totalQty} articles)",
                    'expense_date' => now(),
                ]);

                foreach ($movements as $m) {
                    $m->update(['expense_id' => $expense->id]);
                }
            }

            Log::info('Stock : livraison', ['user_id' => $by->id, 'restaurant_id' => $restaurantId, 'lines' => count($movements), 'total_cost' => $totalCost]);

            return ['lines' => count($movements), 'quantity' => $totalQty, 'total_cost' => $totalCost, 'expense' => $expense];
        });
    }

    /**
     * Perte ou casse : bouteille cassée, périmée, renversée…
     *
     * @throws BusinessRuleException
     */
    public function recordLoss(User $by, int $productId, int $quantity, string $reason): StockMovement
    {
        $this->guard($by);

        if ($quantity < 1 || $quantity > 100000) {
            throw new BusinessRuleException('Entrez une quantité entre 1 et 100 000.');
        }

        if (mb_strlen(trim($reason)) < 3) {
            throw new BusinessRuleException('Indiquez le motif (casse, périmé…).');
        }

        return DB::transaction(function () use ($by, $productId, $quantity, $reason) {
            $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
            $before = $product->stock_quantity;

            if ($quantity > $before) {
                throw new BusinessRuleException("Le stock de {$product->name} n'est que de {$before}.");
            }

            $product->decrement('stock_quantity', $quantity);

            $movement = StockMovement::create([
                'restaurant_id' => $product->restaurant_id,
                'product_id' => $product->id,
                'user_id' => $by->id,
                'type' => 'loss',
                'quantity' => -$quantity,
                'stock_before' => $before,
                'stock_after' => $before - $quantity,
                'unit_cost' => $product->unitValue(),
                'reason' => mb_substr(trim($reason), 0, 255),
            ]);

            Log::info('Stock : perte', ['user_id' => $by->id, 'product_id' => $product->id, 'quantity' => $quantity]);

            return $movement;
        });
    }

    /**
     * Inventaire : on saisit ce qui est réellement compté ; l'application
     * corrige le stock et calcule ce qui manque (ou ce qu'il y a en trop).
     *
     * @param  array<int, int|string|null>  $counted  quantité comptée par id de produit (vide = non compté)
     *
     * @throws BusinessRuleException
     */
    public function recordInventory(User $by, array $counted, ?string $note = null): StockCount
    {
        $this->guard($by);

        $counted = array_filter($counted, fn ($v) => $v !== null && $v !== '');

        if ($counted === []) {
            throw new BusinessRuleException('Saisissez la quantité comptée d\'au moins un produit.');
        }

        foreach ($counted as $value) {
            if (! is_numeric($value) || (int) $value < 0 || (int) $value > 1000000 || (string) (int) $value !== (string) $value) {
                throw new BusinessRuleException('Les quantités comptées doivent être des nombres entiers positifs.');
            }
        }

        return DB::transaction(function () use ($by, $counted, $note) {
            $count = StockCount::create([
                'restaurant_id' => $by->currentRestaurantId(),
                'user_id' => $by->id,
                'note' => $note ? mb_substr(trim($note), 0, 255) : null,
            ]);

            $qtyGap = 0;
            $valueGap = 0;

            foreach ($counted as $productId => $real) {
                $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                $before = $product->stock_quantity;
                $diff = (int) $real - $before;

                if ($diff !== 0) {
                    $product->update(['stock_quantity' => (int) $real]);

                    StockMovement::create([
                        'restaurant_id' => $product->restaurant_id,
                        'product_id' => $product->id,
                        'user_id' => $by->id,
                        'type' => 'inventory',
                        'quantity' => $diff,
                        'stock_before' => $before,
                        'stock_after' => (int) $real,
                        'unit_cost' => $product->unitValue(),
                        'stock_count_id' => $count->id,
                    ]);
                }

                $qtyGap += $diff;
                $valueGap += $diff * $product->unitValue();
            }

            $count->update([
                'products_counted' => count($counted),
                'quantity_gap' => $qtyGap,
                'value_gap' => $valueGap,
            ]);

            Log::info('Stock : inventaire', ['user_id' => $by->id, 'stock_count_id' => $count->id, 'value_gap' => $valueGap]);

            return $count;
        });
    }

    /** Stock de départ à la création d'un produit. */
    public function recordInitial(User $by, Product $product): void
    {
        if ($product->stock_quantity <= 0) {
            return;
        }

        StockMovement::create([
            'restaurant_id' => $product->restaurant_id,
            'product_id' => $product->id,
            'user_id' => $by->id,
            'type' => 'initial',
            'quantity' => $product->stock_quantity,
            'stock_before' => 0,
            'stock_after' => $product->stock_quantity,
            'unit_cost' => $product->cost_price,
        ]);
    }

    /** @throws BusinessRuleException */
    protected function guard(User $by): void
    {
        if (! self::canManage($by)) {
            throw new BusinessRuleException('Seuls le patron et la gérante gèrent le stock.');
        }
    }
}
