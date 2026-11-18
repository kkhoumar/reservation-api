<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Billetage de la clôture : nombre de billets et de pièces comptés,
 * conservé pour pouvoir vérifier le comptage plus tard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->json('cash_count')->nullable()->after('counted_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn('cash_count');
        });
    }
};
