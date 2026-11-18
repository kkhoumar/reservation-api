<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rappels d'échéance envoyés aux maquis (une seule fois par étape et par
 * échéance : J-7, J-3, J-1, jour J, retard, coupure).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);          // J-7 | J-3 | J-1 | J0 | retard | coupe
            $table->date('due_date');            // l'échéance concernée
            $table->string('status', 20);        // sent | failed | manual
            $table->string('phone', 20)->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'kind', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_reminders');
    }
};
