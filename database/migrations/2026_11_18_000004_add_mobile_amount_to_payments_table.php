<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paiement Mobile Money, seul ou mixte :
 * - mobile_amount   : part réglée par Mobile Money (payment_method = l'opérateur) ;
 * - amount_received : espèces reçues pour le reste, change_given : monnaie rendue.
 * Seule la part espèces (amount_received - change_given) entre dans la caisse.
 * Les paiements existants (100 % espèces) restent valables tels quels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('mobile_amount')->default(0)->after('change_given');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('mobile_amount');
        });
    }
};
