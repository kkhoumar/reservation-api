<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->enum('type', ['maquis', 'restaurant', 'bar_restaurant', 'snack'])->default('maquis');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('commune')->nullable();
            $table->string('city')->nullable();
            $table->string('opening_hours')->nullable();

            // Statut d'abonnement — géré manuellement au MVP (voir PRD section 15)
            $table->enum('subscription_status', ['trial', 'active', 'suspended'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
