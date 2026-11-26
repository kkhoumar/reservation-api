<?php

namespace App\Actions;

use App\Exceptions\BusinessRuleException;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Opérations irréversibles du Super Admin sur un maquis :
 * - vider les données de test avant de le remettre au client ;
 * - supprimer complètement le maquis.
 *
 * Requêtes directes en base (pas de modèles) : aucun filtre automatique ni
 * garde-fou de lecture seule ne s'applique, et tout se fait en « tout ou rien ».
 */
class RestaurantDataManager
{
    /**
     * Efface l'activité (ventes, caisse, dépenses, crédits, stock) mais garde
     * l'installation : réglages, équipe, menu, tables, abonnement.
     *
     * @return array<string, int> nombre de lignes effacées par type
     *
     * @throws BusinessRuleException
     */
    public function resetTestData(Restaurant $restaurant, User $admin, bool $deleteCustomers = false, bool $zeroStock = false): array
    {
        $this->guard($admin);

        return DB::transaction(function () use ($restaurant, $admin, $deleteCustomers, $zeroStock) {
            $id = $restaurant->id;
            $orderIds = DB::table('orders')->where('restaurant_id', $id)->pluck('id');
            $registerIds = DB::table('cash_registers')->where('restaurant_id', $id)->pluck('id');

            $counts = [
                'commandes' => $orderIds->count(),
                'paiements' => DB::table('payments')->whereIn('order_id', $orderIds)->count(),
                'caisses' => $registerIds->count(),
                'dépenses' => DB::table('expenses')->where('restaurant_id', $id)->count(),
                'mouvements de crédit' => DB::table('credit_transactions')->where('restaurant_id', $id)->count(),
                'versements de sacoche' => DB::table('cash_remittances')->where('restaurant_id', $id)->count(),
                'mouvements de stock' => DB::table('stock_movements')->where('restaurant_id', $id)->count(),
                'comptages' => DB::table('stock_counts')->where('restaurant_id', $id)->count(),
                'lignes du journal' => DB::table('activity_logs')->where('restaurant_id', $id)->count(),
            ];

            // Ordre : d'abord ce qui dépend des autres
            DB::table('credit_transactions')->where('restaurant_id', $id)->delete();
            DB::table('cash_remittances')->where('restaurant_id', $id)->delete();
            DB::table('cash_movements')->where('restaurant_id', $id)->delete();
            DB::table('stock_movements')->where('restaurant_id', $id)->delete();
            DB::table('stock_counts')->where('restaurant_id', $id)->delete();
            DB::table('activity_logs')->where('restaurant_id', $id)->delete();
            DB::table('payments')->whereIn('order_id', $orderIds)->delete();
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->where('restaurant_id', $id)->delete();
            DB::table('expenses')->where('restaurant_id', $id)->delete();
            DB::table('cash_registers')->where('restaurant_id', $id)->delete();

            if ($deleteCustomers) {
                $counts['clients à crédit'] = DB::table('customers')->where('restaurant_id', $id)->delete();
            }

            // Toutes les tables redeviennent libres
            DB::table('restaurant_tables')->where('restaurant_id', $id)->update(['status' => 'free']);

            if ($zeroStock) {
                DB::table('products')->where('restaurant_id', $id)->update(['stock_quantity' => 0]);
            }

            Log::warning('Super Admin : données de test vidées', [
                'admin_id' => $admin->id,
                'restaurant_id' => $id,
                'counts' => $counts,
                'customers_deleted' => $deleteCustomers,
                'stock_zeroed' => $zeroStock,
            ]);

            return $counts;
        });
    }

    /**
     * Supprime le maquis et tout ce qui lui appartient (équipe, menu, ventes,
     * caisse, abonnement…). Irréversible.
     *
     * @throws BusinessRuleException
     */
    public function deleteRestaurant(Restaurant $restaurant, User $admin): void
    {
        $this->guard($admin);

        DB::transaction(function () use ($restaurant, $admin) {
            $summary = [
                'name' => $restaurant->name,
                'users' => DB::table('users')->where('restaurant_id', $restaurant->id)->count(),
                'orders' => DB::table('orders')->where('restaurant_id', $restaurant->id)->count(),
                'subscription_paid' => (int) DB::table('subscription_payments')->where('restaurant_id', $restaurant->id)->sum('amount'),
            ];

            // Les liens « en cascade » de la base effacent tout le reste
            DB::table('restaurants')->where('id', $restaurant->id)->delete();

            Log::warning('Super Admin : maquis supprimé', ['admin_id' => $admin->id, 'restaurant_id' => $restaurant->id] + $summary);
        });
    }

    /** @throws BusinessRuleException */
    protected function guard(User $admin): void
    {
        if (! $admin->isPlatformAdmin()) {
            throw new BusinessRuleException('Réservé au Super Admin.');
        }
    }
}
