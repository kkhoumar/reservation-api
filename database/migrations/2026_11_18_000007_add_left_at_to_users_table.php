<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Départ d'un employé : il est retiré du maquis (plus de connexion, plus
 * dans la liste) mais jamais effacé, pour garder son historique de ventes,
 * de caisse et de crédit intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('left_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('left_at');
        });
    }
};
