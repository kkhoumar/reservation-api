<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Article envoyé en cuisine : la date d'envoi est notée sur la ligne.
 * Une ligne envoyée ne grossit plus (les ajouts suivants font une nouvelle
 * ligne) et un serveur ne peut plus la diminuer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('note');
        });

        // Commandes déjà parties en cuisine (ou plus loin) : leurs lignes sont envoyées
        DB::table('order_items')
            ->whereIn('order_id', DB::table('orders')->where('status', '!=', 'created')->select('id'))
            ->update(['sent_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('sent_at');
        });
    }
};
