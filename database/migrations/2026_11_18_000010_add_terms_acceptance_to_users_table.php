<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acceptation des conditions d'utilisation par le patron : quand, et
 * quelle version (pour redemander l'accord quand les conditions changent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('must_change_password');
            $table->string('terms_version', 20)->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'terms_version']);
        });
    }
};
