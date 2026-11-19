<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comptage du stock :
 * - categories.kind : « drink » (boissons) ou « food » (nourriture), pour
 *   séparer les totaux ;
 * - stock_counts : période couverte (depuis le comptage précédent) et
 *   totaux en FCFA ;
 * - stock_count_lines : la photo de chaque produit compté (attendu,
 *   compté, écart, prix, mouvements de la période, raison de l'écart).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('kind', 10)->default('food')->after('name');
        });

        foreach (DB::table('categories')->get(['id', 'name']) as $category) {
            DB::table('categories')->where('id', $category->id)->update(['kind' => Category::guessKind($category->name)]);
        }

        Schema::table('stock_counts', function (Blueprint $table) {
            $table->timestamp('previous_count_at')->nullable()->after('note');
            $table->integer('gap_sale_value')->default(0)->after('value_gap');   // écart au prix de vente
            $table->bigInteger('cost_value')->default(0)->after('gap_sale_value'); // compté × prix d'achat
            $table->bigInteger('sale_value')->default(0)->after('cost_value');     // compté × prix de vente
        });

        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('kind', 10);
            $table->integer('expected');
            $table->integer('counted');
            $table->integer('gap');
            $table->unsignedInteger('unit_cost')->nullable();
            $table->unsignedInteger('unit_price');
            $table->integer('delivered')->default(0);
            $table->integer('sold')->default(0);
            $table->integer('lost')->default(0);
            $table->string('reason', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');

        Schema::table('stock_counts', function (Blueprint $table) {
            $table->dropColumn(['previous_count_at', 'gap_sale_value', 'cost_value', 'sale_value']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
