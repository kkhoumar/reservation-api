<?php

namespace App\Actions;

use App\Exceptions\BusinessRuleException;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Vente de secours : faite au comptoir sans internet, gardée dans le téléphone,
 * puis envoyée ici quand la connexion revient.
 *
 * Règles :
 * - espèces seulement, ni remise ni offert ;
 * - chaque vente a un numéro unique (créé par le téléphone) : renvoyée deux
 *   fois, elle n'est enregistrée qu'une fois ;
 * - les prix viennent de la base. Si le prix a changé entre-temps, la vente est
 *   gardée telle qu'encaissée et l'écart est signalé au patron ;
 * - l'argent entre dans la caisse ouverte au moment de l'envoi ; l'heure réelle
 *   de la vente est gardée à part ;
 * - la bouteille a été vendue pour de vrai : le stock baisse même s'il
 *   devient négatif, et le patron est prévenu.
 */
class RecordOfflineSale
{
    /** Une vente de secours plus vieille que ça est refusée (téléphone oublié, horloge fausse). */
    public const MAX_AGE_HOURS = 72;

    public const MAX_LINES = 40;

    public static function canUse(?User $user): bool
    {
        return $user !== null
            && $user->currentRestaurantId() !== null
            && ($user->isOwner() || $user->isManager() || $user->isCashier());
    }

    /**
     * @param  array{uuid?: mixed, sold_at?: mixed, cash_received?: mixed, total?: mixed, items?: mixed}  $sale
     * @return array{uuid: string, status: string, message: string, order_number?: string}
     *         status : saved | duplicate | wait (caisse fermée, à renvoyer) | rejected
     */
    public static function handle(User $cashier, array $sale): array
    {
        $uuid = is_string($sale['uuid'] ?? null) ? strtolower($sale['uuid']) : '';

        try {
            $data = self::validate($sale, $uuid);
        } catch (BusinessRuleException $e) {
            return ['uuid' => $uuid, 'status' => 'rejected', 'message' => $e->getMessage()];
        }

        // Déjà reçue (envoi répété après une coupure) : on ne la compte pas deux fois
        $existing = Order::withoutGlobalScopes()->where('offline_uuid', $uuid)->first();

        if ($existing) {
            return $existing->restaurant_id === $cashier->currentRestaurantId()
                ? ['uuid' => $uuid, 'status' => 'duplicate', 'message' => 'Déjà enregistrée.', 'order_number' => $existing->order_number]
                : ['uuid' => $uuid, 'status' => 'rejected', 'message' => 'Numéro de vente invalide.'];
        }

        if (! CashRegister::isOpenFor($cashier->currentRestaurantId())) {
            return ['uuid' => $uuid, 'status' => 'wait', 'message' => 'La caisse est fermée. Ouvrez la caisse pour envoyer les ventes de secours.'];
        }

        try {
            $order = DB::transaction(fn () => self::store($cashier, $uuid, $data));
        } catch (BusinessRuleException $e) {
            return ['uuid' => $uuid, 'status' => 'rejected', 'message' => $e->getMessage()];
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Deux envois au même instant : l'autre a gagné
            $order = Order::withoutGlobalScopes()->where('offline_uuid', $uuid)->first();

            return ['uuid' => $uuid, 'status' => 'duplicate', 'message' => 'Déjà enregistrée.', 'order_number' => $order?->order_number ?? ''];
        }

        return ['uuid' => $uuid, 'status' => 'saved', 'message' => 'Enregistrée.', 'order_number' => $order->order_number];
    }

    /**
     * @return array{sold_at: CarbonImmutable, cash: int, total: int, items: array<int, int>}
     *
     * @throws BusinessRuleException
     */
    private static function validate(array $sale, string $uuid): array
    {
        if (! Str::isUuid($uuid)) {
            throw new BusinessRuleException('Numéro de vente invalide.');
        }

        try {
            $soldAt = CarbonImmutable::parse((string) ($sale['sold_at'] ?? ''));
        } catch (\Throwable) {
            throw new BusinessRuleException('Heure de vente invalide.');
        }

        if ($soldAt->isAfter(now()->addMinutes(10)) || $soldAt->isBefore(now()->subHours(self::MAX_AGE_HOURS))) {
            throw new BusinessRuleException('Heure de vente impossible : vérifiez la date et l\'heure du téléphone.');
        }

        $items = [];

        foreach ((array) ($sale['items'] ?? []) as $line) {
            $productId = filter_var($line['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT);

            if (! $productId || ! $quantity || $quantity < 1 || $quantity > 999) {
                throw new BusinessRuleException('Article invalide dans la vente.');
            }

            $items[$productId] = ($items[$productId] ?? 0) + $quantity;
        }

        if ($items === [] || count($items) > self::MAX_LINES) {
            throw new BusinessRuleException('Vente vide ou trop longue.');
        }

        $cash = filter_var($sale['cash_received'] ?? null, FILTER_VALIDATE_INT);
        $total = filter_var($sale['total'] ?? null, FILTER_VALIDATE_INT);

        if ($cash === false || $total === false || $total <= 0 || $cash < $total || $cash > 10_000_000) {
            throw new BusinessRuleException('Montants invalides : l\'argent reçu doit couvrir le total.');
        }

        return ['sold_at' => $soldAt, 'cash' => $cash, 'total' => $total, 'items' => $items];
    }

    /** @throws BusinessRuleException */
    private static function store(User $cashier, string $uuid, array $data): Order
    {
        // Produits du maquis seulement (la portée par maquis s'applique ici)
        $products = Product::whereIn('id', array_keys($data['items']))->lockForUpdate()->get()->keyBy('id');

        if ($products->count() !== count($data['items'])) {
            throw new BusinessRuleException('Un article de la vente n\'existe plus dans ce maquis.');
        }

        $order = Order::create([
            'restaurant_id' => $cashier->currentRestaurantId(),
            'table_id' => null,
            'user_id' => $cashier->id,
            'order_type' => 'takeaway',
            'status' => 'awaiting_payment',
        ]);
        $order->forceFill(['offline_uuid' => $uuid, 'offline_sold_at' => $data['sold_at']])->save();

        $lines = [];
        $shortages = [];

        foreach ($data['items'] as $productId => $quantity) {
            $product = $products[$productId];

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->selling_price,
                'unit_cost' => $product->cost_price,
                'total' => $product->selling_price * $quantity,
            ]);

            if ($product->track_stock && $product->stock_quantity < $quantity) {
                $shortages[] = "{$product->name} (il en restait {$product->stock_quantity})";
            }

            $product->decrementStock($quantity);
            $lines[] = "{$quantity} × {$product->name}";
        }

        $order->recalculateTotal();
        $order->refresh();

        // Ce que le client a vraiment payé au comptoir (prix gardés dans le téléphone)
        $chargedTotal = $data['total'];
        $priceGap = $chargedTotal - (int) $order->total;

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $cashier->id,
            'payment_method' => 'cash',
            'amount_due' => $order->total,
            'amount_received' => $data['cash'],
            // La monnaie réellement rendue au client : le tiroir reçoit exactement le total encaissé
            'change_given' => $data['cash'] - $chargedTotal,
            'mobile_amount' => 0,
            'status' => 'completed',
            'in_sacoche' => false,
            'paid_at' => now(),
        ]);

        $order->markAsPaid();

        $warnings = [];

        if ($priceGap !== 0) {
            $warnings[] = 'prix différents de ceux du moment : encaissé ' . ActivityLog::fcfa($chargedTotal) . ' au lieu de ' . ActivityLog::fcfa((int) $order->total);
        }

        if ($shortages !== []) {
            $warnings[] = 'stock insuffisant pour ' . implode(', ', $shortages);
        }

        ActivityLog::record(
            'offline_sale',
            'a fait une vente de secours sans internet à ' . $data['sold_at']->timezone(config('app.timezone'))->format('H\hi')
                . ' : ' . implode(', ', $lines) . '. ' . ActivityLog::fcfa($chargedTotal) . ' en espèces.'
                . ($warnings ? ' À vérifier : ' . implode(' ; ', $warnings) . '.' : ''),
            [
                'user' => $cashier,
                'amount' => $chargedTotal,
                'meta' => ['order_id' => $order->id, 'uuid' => $uuid, 'sold_at' => $data['sold_at']->toIso8601String(), 'check' => $warnings !== []],
            ]
        );

        return $order;
    }
}
