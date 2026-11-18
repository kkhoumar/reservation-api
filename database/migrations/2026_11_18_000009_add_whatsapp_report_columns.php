<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rapport du soir envoyé automatiquement sur WhatsApp :
 * - réglage du maquis (activé, numéro qui le reçoit) ;
 * - suivi de l'envoi sur chaque clôture de caisse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('whatsapp_report_enabled')->default(true)->after('payment_timing');
            // Vide = numéro du patron
            $table->string('report_phone', 20)->nullable()->after('whatsapp_report_enabled');
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            // pending | sent | failed | skipped
            $table->string('report_status', 20)->nullable()->after('closing_note');
            $table->string('report_phone', 20)->nullable()->after('report_status');
            $table->timestamp('report_sent_at')->nullable()->after('report_phone');
            $table->string('report_error', 255)->nullable()->after('report_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['report_status', 'report_phone', 'report_sent_at', 'report_error']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_report_enabled', 'report_phone']);
        });
    }
};
