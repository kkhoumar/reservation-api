<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - activity_logs : journal des actions (qui, quoi, quand). Une ligne ne se
 *   modifie ni ne s'efface depuis l'application.
 * - orders.cancelled_by / cancelled_at : qui a annulé un bon, et quand.
 * - users.avatar_path : photo de profil (fichier privé, servi au même maquis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 120)->nullable(); // nom gardé même si le compte disparaît
            $table->string('action', 40);
            $table->string('description', 500);
            $table->integer('amount')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['restaurant_id', 'created_at']);
            $table->index(['restaurant_id', 'user_id']);
            $table->index(['restaurant_id', 'action']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn('cancelled_at');
        });

        Schema::dropIfExists('activity_logs');
    }
};
