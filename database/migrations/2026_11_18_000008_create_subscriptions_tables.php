<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnements des maquis à la plateforme :
 * - plans : les formules (prix mensuel modifiable par le Super Admin) ;
 * - restaurants.plan_id / paid_until : formule choisie, accès payé jusqu'au ;
 * - subscription_payments : chaque paiement reçu d'un maquis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 60);
            $table->unsignedInteger('monthly_price');
            $table->unsignedSmallInteger('max_users')->nullable(); // null = illimité
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Formules de départ : prix à valider, modifiables dans l'espace Super Admin
        $now = now();
        DB::table('plans')->insert([
            ['code' => 'basic', 'name' => 'Basic', 'monthly_price' => 5000, 'max_users' => 3, 'description' => 'Petit maquis : le patron et 2 personnes.', 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'pro', 'name' => 'Pro', 'monthly_price' => 10000, 'max_users' => 8, 'description' => 'Maquis avec serveuses et comptoir, jusqu\'à 8 personnes.', 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'premium', 'name' => 'Premium', 'monthly_price' => 20000, 'max_users' => null, 'description' => 'Grand établissement : personnel illimité.', 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('restaurants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_status')->constrained()->nullOnDelete();
            $table->timestamp('paid_until')->nullable()->after('trial_ends_at');
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedSmallInteger('months');
            $table->string('method', 30);
            $table->string('reference', 100)->nullable();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn('paid_until');
        });

        Schema::dropIfExists('plans');
    }
};
