<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nouveau rôle « associate » (associé) : consulte tout, ne modifie rien.
 * La colonne users.role était une liste fermée ; elle devient un texte court.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('server')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'associate')->update(['role' => 'manager']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('owner', 'manager', 'server', 'cashier'))");
        }
    }
};
