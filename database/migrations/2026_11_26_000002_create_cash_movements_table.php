<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * « Argent du tiroir » : de l'argent ajouté (monnaie oubliée à l'ouverture,
 * apportée par le patron) ou retiré (le patron récupère la recette) pendant
 * que la caisse est ouverte. Ce n'est ni une vente ni une dépense.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 3); // in | out
            $table->unsignedInteger('amount');
            $table->string('reason', 30);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['cash_register_id', 'type']);
            $table->index(['restaurant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
