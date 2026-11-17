<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('status');
        });

        // Numéros existants mis au format unique « 0711223344 »
        // (sauf si cela créerait un doublon : on laisse alors tel quel).
        foreach (DB::table('users')->select('id', 'phone')->get() as $user) {
            $normalized = User::normalizePhone($user->phone);

            if ($normalized === $user->phone) {
                continue;
            }

            $taken = DB::table('users')->where('phone', $normalized)->where('id', '!=', $user->id)->exists();

            if (! $taken) {
                DB::table('users')->where('id', $user->id)->update(['phone' => $normalized]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
