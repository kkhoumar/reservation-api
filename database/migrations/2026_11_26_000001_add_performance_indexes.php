<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Index pour que l'application reste rapide quand il y a beaucoup de maquis
 * et des mois de ventes. Sur PostgreSQL, une clé étrangère ne crée pas d'index
 * toute seule : sans eux, chaque recherche lit toute la table.
 *
 * Ajoute seulement : aucune donnée n'est modifiée.
 */
return new class extends Migration
{
    private const INDEXES = [
        'orders' => [['restaurant_id', 'status', 'created_at'], ['table_id'], ['user_id'], ['customer_id']],
        'order_items' => [['order_id'], ['product_id']],
        'payments' => [['order_id'], ['restaurant_id', 'paid_at'], ['cash_register_id']],
        'products' => [['restaurant_id', 'category_id']],
        'categories' => [['restaurant_id']],
        'cash_registers' => [['restaurant_id', 'closed_at']],
        'credit_transactions' => [['restaurant_id', 'customer_id'], ['order_id']],
        'expenses' => [['restaurant_id', 'expense_date'], ['cash_register_id']],
        'restaurant_tables' => [['restaurant_id']],
        'customers' => [['restaurant_id']],
        'users' => [['restaurant_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $columns) {
                if (! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $this->name($table, $columns))) {
                    continue;
                }
                Schema::table($table, fn ($t) => $t->index($columns, $this->name($table, $columns)));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $columns) {
                if (Schema::hasTable($table) && Schema::hasIndex($table, $this->name($table, $columns))) {
                    Schema::table($table, fn ($t) => $t->dropIndex($this->name($table, $columns)));
                }
            }
        }
    }

    private function name(string $table, array $columns): string
    {
        return 'perf_' . $table . '_' . implode('_', $columns);
    }
};
