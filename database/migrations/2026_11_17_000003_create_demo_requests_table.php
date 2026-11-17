<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('contact_name', 120);
            $table->string('restaurant_name', 120);
            $table->string('phone', 20);
            $table->string('commune', 80)->nullable();
            $table->string('message', 500)->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'rejected'])->default('new');
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
