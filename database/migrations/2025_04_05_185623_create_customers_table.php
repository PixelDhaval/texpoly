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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->foreignId('label_id')->nullable()->constrained('labels')->onDelete('cascade');
            $table->string('short_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_qr')->default(true);
            $table->boolean('is_bale_no')->default(true);
            $table->boolean('is_printed_by')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
