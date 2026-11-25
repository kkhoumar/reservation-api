<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un plat ne se compte pas comme une bouteille : « Suivre le stock » devient
 * un choix par produit (non par défaut pour la nourriture). Quand la cuisine
 * n'en a plus, « Fini pour aujourd'hui » le bloque jusqu'au lendemain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('track_stock')->default(true)->after('stock_quantity');
            $table->date('sold_out_on')->nullable()->after('track_stock');
        });

        // La nourriture déjà saisie ne bloque plus faute de stock
        DB::table('products')
            ->whereIn('category_id', DB::table('categories')->where('kind', 'food')->select('id'))
            ->update(['track_stock' => false]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['track_stock', 'sold_out_on']);
        });
    }
};
