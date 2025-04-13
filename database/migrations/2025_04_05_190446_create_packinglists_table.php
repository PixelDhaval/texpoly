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
        Schema::create('packinglists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('label_name')->nullable();
            $table->integer('customer_qty')->default(0);
            $table->string('unit')->nullable();
            $table->decimal('price', 8, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('weight')->default(0);
            $table->boolean('is_bold')->default(false);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packinglists');
    }
};
