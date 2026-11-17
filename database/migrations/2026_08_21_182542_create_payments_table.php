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
        Schema::create('payments', function (Blueprint $table) {
              $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('payment_method', [
                'cash', 'orange_money', 'mtn_momo', 'moov_money', 'wave', 'card',
            ])->default('cash');
            $table->unsignedInteger('amount_due');
            $table->unsignedInteger('amount_received');
            $table->unsignedInteger('change_given')->default(0);
            $table->string('reference')->nullable();
            $table->enum('status', ['completed', 'failed'])->default('completed');
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
