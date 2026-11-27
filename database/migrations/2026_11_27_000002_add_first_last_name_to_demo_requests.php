<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demande de démo : prénom et nom demandés séparément, pour que le compte
 * du patron soit créé avec son vrai prénom (« YAO Adjoua » n'est plus deviné).
 * Les anciennes demandes gardent seulement « contact_name ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('demo_requests', 'contact_first_name')) {
                $table->string('contact_first_name', 80)->nullable()->after('contact_name');
            }
            if (! Schema::hasColumn('demo_requests', 'contact_last_name')) {
                $table->string('contact_last_name', 80)->nullable()->after('contact_first_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            foreach (['contact_first_name', 'contact_last_name'] as $col) {
                if (Schema::hasColumn('demo_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
