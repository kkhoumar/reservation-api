<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock tracé :
 * - products.cost_price : prix d'achat (dernière livraison) ;
 * - order_items.unit_cost : prix d'achat figé au moment de la vente (bénéfice) ;
 * - stock_counts : séances d'inventaire ;
 * - stock_movements : chaque mouvement hors vente (livraison, perte,
 *   inventaire, stock de départ), avec qui, quand, combien et pourquoi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('cost_price')->nullable()->after('selling_price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_cost')->nullable()->after('unit_price');
        });

        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('products_counted')->default(0);
            $table->integer('quantity_gap')->default(0);   // négatif = manquant
            $table->integer('value_gap')->default(0);      // en FCFA, au prix d'achat (ou de vente)
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // initial | delivery | loss | inventory
            $table->string('type', 20);
            $table->integer('quantity');                    // + entrée, − sortie
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->unsignedInteger('unit_cost')->nullable();
            $table->string('supplier', 120)->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignId('stock_count_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['restaurant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_counts');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
