<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le rapport du soir automatique sur WhatsApp (qui coûte un message Meta)
 * est inclus dans certaines formules seulement : Pro et Premium au départ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('includes_auto_report')->default(false)->after('max_users');
        });

        DB::table('plans')->whereIn('code', ['pro', 'premium'])->update(['includes_auto_report' => true]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('includes_auto_report');
        });
    }
};
