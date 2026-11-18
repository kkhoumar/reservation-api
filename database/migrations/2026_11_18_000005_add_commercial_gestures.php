<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestes commerciaux, toujours tracés (qui, pourquoi) :
 * - article offert : la ligne passe à 0 F, le stock sort normalement ;
 * - remise : un montant retiré du total du bon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_offered')->default(false)->after('total');
            $table->string('offered_reason', 255)->nullable()->after('is_offered');
            $table->foreignId('offered_by')->nullable()->after('offered_reason')->constrained('users')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('discount_amount')->default(0)->after('subtotal');
            $table->string('discount_reason', 255)->nullable()->after('discount_amount');
            $table->foreignId('discount_by')->nullable()->after('discount_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_by');
            $table->dropColumn(['discount_amount', 'discount_reason']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offered_by');
            $table->dropColumn(['is_offered', 'offered_reason']);
        });
    }
};
