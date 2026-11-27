<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Reçus des dépenses : d'où vient la photo (prise sur place ou choisie dans
 *   le téléphone) et sa date, pour que le patron repère une vieille photo.
 * - Montant à partir duquel la photo du reçu est obligatoire (0 = toujours).
 * - Fin du guide « Pour bien démarrer » : le message « Bravo » ne s'affiche qu'une fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'receipt_source')) {
                $table->string('receipt_source', 10)->nullable()->after('receipt_path');
            }
            if (! Schema::hasColumn('expenses', 'receipt_taken_at')) {
                $table->timestamp('receipt_taken_at')->nullable()->after('receipt_source');
            }
        });

        Schema::table('restaurants', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurants', 'receipt_required_from')) {
                $table->unsignedInteger('receipt_required_from')->default(0)->after('report_phone');
            }
            if (! Schema::hasColumn('restaurants', 'setup_done_at')) {
                $table->timestamp('setup_done_at')->nullable()->after('receipt_required_from');
            }
        });

        // Les maquis qui vendent déjà ne verront pas le guide ni le « Bravo »
        DB::table('restaurants')
            ->whereNull('setup_done_at')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->whereColumn('orders.restaurant_id', 'restaurants.id'))
            ->update(['setup_done_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            foreach (['receipt_source', 'receipt_taken_at'] as $col) {
                if (Schema::hasColumn('expenses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('restaurants', function (Blueprint $table) {
            foreach (['receipt_required_from', 'setup_done_at'] as $col) {
                if (Schema::hasColumn('restaurants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
