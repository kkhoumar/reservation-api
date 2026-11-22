<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventes de secours (sans internet) : chaque vente porte un numéro unique créé
 * par le téléphone, pour ne jamais être enregistrée deux fois, et l'heure réelle
 * de la vente (l'argent entre dans la caisse ouverte au moment de l'envoi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('offline_uuid')->nullable()->unique();
            $table->timestamp('offline_sold_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['offline_uuid']);
            $table->dropColumn(['offline_uuid', 'offline_sold_at']);
        });
    }
};
