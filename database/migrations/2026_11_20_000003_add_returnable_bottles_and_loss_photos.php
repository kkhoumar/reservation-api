<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bouteilles consignées et preuves de perte :
 * - products.is_returnable : bouteille en verre rendue au fournisseur (1 vide = 1 pleine) ;
 * - products.crate_size : bouteilles par casier ;
 * - products.empty_quantity : vides que l'application attend au maquis ;
 * - products.empties_counted_at : premier comptage des vides (sans lui, pas de contrôle) ;
 * - stock_movements.photo_path : photo d'une perte ;
 * - stock_movements.empties_surplus : vides rendus en plus de ce qui était attendu ;
 * - stock_count_lines : vides attendus / comptés, vendues sans bon, sorties du maquis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_returnable')->default(false);
            $table->unsignedSmallInteger('crate_size')->nullable();
            $table->integer('empty_quantity')->default(0);
            $table->timestamp('empties_counted_at')->nullable();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->integer('empties_surplus')->nullable();
        });

        Schema::table('stock_count_lines', function (Blueprint $table) {
            $table->integer('empties_expected')->nullable();
            $table->integer('empties_counted')->nullable();
            $table->integer('unbilled')->default(0);
            $table->integer('taken_out')->default(0);
        });

        Schema::table('stock_counts', function (Blueprint $table) {
            $table->integer('unbilled_value')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('stock_counts', fn (Blueprint $t) => $t->dropColumn('unbilled_value'));
        Schema::table('stock_count_lines', fn (Blueprint $t) => $t->dropColumn(['empties_expected', 'empties_counted', 'unbilled', 'taken_out']));
        Schema::table('stock_movements', fn (Blueprint $t) => $t->dropColumn(['photo_path', 'empties_surplus']));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['is_returnable', 'crate_size', 'empty_quantity', 'empties_counted_at']));
    }
};
