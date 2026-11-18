<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La sacoche : l'argent encaissé par une serveuse reste dans sa sacoche
 * jusqu'à ce qu'elle le verse à la caisse.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Figé au moment du paiement : encaissé par une serveuse → sa sacoche
        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('in_sacoche')->default(false)->after('status');
        });

        Schema::create('cash_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_remittances');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('in_sacoche');
        });
    }
};
