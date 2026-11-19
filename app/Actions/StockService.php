<?php

namespace App\Actions;

use App\Exceptions\BusinessRuleException;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\StockCountSheet;
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

    /** Consulter le stock et les comptages : aussi l'associé (sans rien modifier). */
    public static function canView(User $user): bool
    {
        return self::canManage($user) || $user->isAssociate();
    }

    /**
     * Livraison (bon de livraison) : plusieurs produits d'un coup.
     *
     * @param  array<int, array{quantity: int, unit_cost: ?int}>  $lines  par id de produit
     * @param  bool  $paidFromRegister  payée avec l'argent de la caisse : crée la dépense
     * @return array{lines: int, quantity: int, total_cost: int, expense: ?Expense, empties_alerts: array<int, array{product: string, surplus: int, value: int}>}
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
            $emptiesAlerts = [];

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

                // Bouteilles consignées : le fournisseur reprend autant de vides que de pleines livrées.
                // Plus de vides rendus que l'application n'en attendait = bières vendues sans bon.
                $surplus = null;
                if ($product->is_returnable) {
                    $expectedEmpties = max(0, (int) $product->empty_quantity);
                    if ($product->hasEmptiesBaseline() && $quantity > $expectedEmpties) {
                        $surplus = $quantity - $expectedEmpties;
                        $emptiesAlerts[] = ['product' => $product->name, 'surplus' => $surplus, 'value' => $surplus * (int) $product->selling_price];
                    }
                    $product->empty_quantity = max(0, $expectedEmpties - $quantity);
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
                    'empties_surplus' => $surplus,
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
            ActivityLog::record('stock_delivery', "a enregistré une livraison de {$totalQty} " . ($totalQty > 1 ? 'articles' : 'article')
                . ($supplier ? " ({$supplier})" : '') . ($totalCost > 0 ? ', ' . ActivityLog::fcfa($totalCost) : '')
                . ($expense ? ', payée avec la caisse.' : '.'), ['user' => $by, 'amount' => -$totalCost]);

            foreach ($emptiesAlerts as $alert) {
                ActivityLog::record('empties_alert', "Livraison : {$alert['surplus']} vides de {$alert['product']} rendus en plus de ce que l'application attendait. "
                    . 'Ce sont probablement des bouteilles vendues sans bon, soit ' . ActivityLog::fcfa($alert['value']) . '.', [
                    'user' => $by, 'amount' => -$alert['value'],
                ]);
            }

            return ['lines' => count($movements), 'quantity' => $totalQty, 'total_cost' => $totalCost, 'expense' => $expense, 'empties_alerts' => $emptiesAlerts];
        });
    }

    /**
     * Perte ou casse : bouteille cassée, périmée, renversée…
     *
     * @throws BusinessRuleException
     */
    public function recordLoss(User $by, int $productId, int $quantity, string $reason, ?string $photoPath = null): StockMovement
    {
        $this->guard($by);

        if ($quantity < 1 || $quantity > 100000) {
            throw new BusinessRuleException('Entrez une quantité entre 1 et 100 000.');
        }

        if (mb_strlen(trim($reason)) < 3) {
            throw new BusinessRuleException('Indiquez le motif (casse, périmé…).');
        }

        return DB::transaction(function () use ($by, $productId, $quantity, $reason, $photoPath) {
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
                'photo_path' => $photoPath,
            ]);

            Log::info('Stock : perte', ['user_id' => $by->id, 'product_id' => $product->id, 'quantity' => $quantity]);
            ActivityLog::record('stock_loss', "a déclaré une perte de {$quantity} × {$product->name}, soit " . ActivityLog::fcfa($quantity * $product->unitValue()) . '. Motif : ' . trim($reason) . ($photoPath ? '. Photo jointe.' : '. Sans photo.'), [
                'user' => $by, 'amount' => -($quantity * $product->unitValue()), 'meta' => ['product_id' => $product->id],
            ]);

            return $movement;
        });
    }

    /**
     * Comptage du stock : on saisit ce qui est réellement là ; l'application
     * compare avec son stock, corrige, et garde la photo de chaque produit
     * (prix du jour, livraisons / ventes / pertes depuis le comptage précédent).
     *
     * @param  array<int, int|string|null>  $counted  quantité comptée par id de produit (vide = non compté)
     * @param  array<int, string|null>  $reasons  raison de l'écart par id de produit (voir StockCountLine::REASONS)
     * @param  array<int, int|string|null>  $empties  vides comptés par id de produit (bouteilles consignées)
     *
     * @throws BusinessRuleException
     */
    public function recordInventory(User $by, array $counted, ?string $note = null, array $reasons = [], array $empties = []): StockCount
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

        foreach ($empties as $value) {
            if ($value !== null && $value !== '' && (! is_numeric($value) || (int) $value < 0 || (int) $value > 1000000 || (string) (int) $value !== (string) $value)) {
                throw new BusinessRuleException('Les vides comptés doivent être des nombres entiers positifs.');
            }
        }

        return DB::transaction(function () use ($by, $counted, $note, $reasons, $empties) {
            $restaurantId = $by->currentRestaurantId();
            $sheet = new StockCountSheet($restaurantId);
            $delivered = $sheet->delivered();
            $sold = $sheet->sold();
            $lost = $sheet->lost();

            $count = StockCount::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $by->id,
                'note' => $note ? mb_substr(trim($note), 0, 255) : null,
                'previous_count_at' => $sheet->since,
            ]);

            $totals = ['qty' => 0, 'gap_cost' => 0, 'gap_sale' => 0, 'cost' => 0, 'sale' => 0, 'unbilled' => 0];

            foreach ($counted as $productId => $real) {
                $product = Product::with('category')->whereKey($productId)->lockForUpdate()->firstOrFail();
                $real = (int) $real;
                $before = $product->stock_quantity;
                $diff = $real - $before;
                $reason = $diff !== 0 && array_key_exists($reasons[$productId] ?? '', StockCountLine::REASONS) ? $reasons[$productId] : null;

                // Vides : ils disent ce que sont devenues les bouteilles qui manquent
                $emptiesExpected = null;
                $emptiesCounted = null;
                $unbilled = 0;
                $takenOut = 0;
                if ($product->is_returnable && isset($empties[$productId]) && $empties[$productId] !== '') {
                    $emptiesCounted = (int) $empties[$productId];
                    if ($product->hasEmptiesBaseline()) {
                        $emptiesExpected = max(0, (int) $product->empty_quantity);
                        if ($diff < 0) {
                            $missing = -$diff;
                            $unbilled = max(0, min($missing, $emptiesCounted - $emptiesExpected));
                            $takenOut = $missing - $unbilled;
                        }
                    }
                    $product->forceFill(['empty_quantity' => $emptiesCounted, 'empties_counted_at' => now()])->save();
                }

                if ($diff !== 0) {
                    $product->update(['stock_quantity' => $real]);

                    StockMovement::create([
                        'restaurant_id' => $product->restaurant_id,
                        'product_id' => $product->id,
                        'user_id' => $by->id,
                        'type' => 'inventory',
                        'quantity' => $diff,
                        'stock_before' => $before,
                        'stock_after' => $real,
                        'unit_cost' => $product->unitValue(),
                        'reason' => $reason ? StockCountLine::REASONS[$reason] : null,
                        'stock_count_id' => $count->id,
                    ]);
                }

                $count->lines()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'kind' => $product->category?->kind ?? 'food',
                    'expected' => $before,
                    'counted' => $real,
                    'gap' => $diff,
                    'unit_cost' => $product->cost_price,
                    'unit_price' => $product->selling_price,
                    'delivered' => (int) ($delivered[$product->id] ?? 0),
                    'sold' => (int) ($sold[$product->id] ?? 0),
                    'lost' => (int) ($lost[$product->id] ?? 0),
                    'reason' => $reason,
                    'empties_expected' => $emptiesExpected,
                    'empties_counted' => $emptiesCounted,
                    'unbilled' => $unbilled,
                    'taken_out' => $takenOut,
                ]);
                $totals['unbilled'] += $unbilled * $product->selling_price;

                $totals['qty'] += $diff;
                $totals['gap_cost'] += $diff * $product->unitValue();
                $totals['gap_sale'] += $diff * $product->selling_price;
                $totals['cost'] += $real * $product->unitValue();
                $totals['sale'] += $real * $product->selling_price;
            }

            $count->update([
                'products_counted' => count($counted),
                'quantity_gap' => $totals['qty'],
                'value_gap' => $totals['gap_cost'],
                'gap_sale_value' => $totals['gap_sale'],
                'cost_value' => $totals['cost'],
                'sale_value' => $totals['sale'],
                'unbilled_value' => $totals['unbilled'],
            ]);

            Log::info('Stock : comptage', ['user_id' => $by->id, 'stock_count_id' => $count->id, 'value_gap' => $totals['gap_cost']]);
            ActivityLog::record('stock_count', 'a validé un comptage de ' . count($counted) . ' ' . (count($counted) > 1 ? 'produits' : 'produit') . ' : '
                . ($totals['gap_sale'] === 0 ? 'tout est juste.' : 'écart de ' . ($totals['gap_sale'] > 0 ? '+' : '') . ActivityLog::fcfa($totals['gap_sale']) . ' au prix de vente.')
                . ($totals['unbilled'] > 0 ? ' Dont ' . ActivityLog::fcfa($totals['unbilled']) . ' de boissons vendues sans bon (vides en trop).' : ''), [
                'user' => $by, 'amount' => $totals['gap_sale'], 'meta' => ['stock_count_id' => $count->id],
            ]);

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
