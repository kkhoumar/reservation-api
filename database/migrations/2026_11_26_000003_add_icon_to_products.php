<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Petite image des produits : le dessin choisi par le patron (bière verte,
 * canette, plat…). Vide = l'application choisit toute seule d'après le nom.
 * La vraie photo, elle, va dans la colonne « photo » qui existait déjà.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'icon')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('icon', 20)->nullable()->after('photo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'icon')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
