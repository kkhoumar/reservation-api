<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moment du paiement :
 * - before : on paie au comptoir avant d'être servi (habitude des maquis) ;
 * - after  : on consomme et on paie à la fin (restaurant, lounge).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->enum('payment_timing', ['before', 'after'])->default('before')->after('credit_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('payment_timing');
        });
    }
};
