<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bouteilles vides cassées notées au comptage : une bière bue ici dont le
 * vide s'est cassé ne doit pas passer pour une bière « disparue ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_count_lines', function (Blueprint $table) {
            $table->unsignedInteger('empties_broken')->nullable()->after('empties_counted');
        });
    }

    public function down(): void
    {
        Schema::table('stock_count_lines', function (Blueprint $table) {
            $table->dropColumn('empties_broken');
        });
    }
};
