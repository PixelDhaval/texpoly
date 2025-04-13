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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('order_date')->nullable();
            $table->string('order_no')->nullable();
            $table->enum('status', ['production', 'draft', 'delivered'])->default('production');
            $table->string('container_no')->nullable();
            $table->string('sgs_seal_no')->nullable();
            $table->string('line_seal_no')->nullable();
            $table->date(('target_date'))->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
