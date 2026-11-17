<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les 2 seuls réglages d'un maquis :
     * - service_mode : tables, vente rapide, ou les deux ;
     * - credit_enabled : ardoise (crédit client) activée ou non.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->enum('service_mode', ['tables', 'quick', 'both'])->default('both')->after('opening_hours');
            $table->boolean('credit_enabled')->default(false)->after('service_mode');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['service_mode', 'credit_enabled']);
        });
    }
};
