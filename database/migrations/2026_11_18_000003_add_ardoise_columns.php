<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ardoise :
 * - plafond facultatif par client (au-delà, le patron doit d'abord encaisser) ;
 * - un remboursement en espèces est rattaché à la caisse ouverte qui l'a reçu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('credit_limit')->nullable()->after('phone');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreignId('cash_register_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_register_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('credit_limit');
        });
    }
};
