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
        Schema::create('cancel_bales', function (Blueprint $table) {
            $table->id();
            $table->string('bale_no')->unique();
            $table->foreignId('packinglist_id')->nullable()->constrained('packinglists')->onDelete('cascade');
            $table->foreignId('qc')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('finalist')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('type', ['production', 'repacking', 'inward', 'outward', 'cutting'])->default('production');
            $table->foreignId('plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->foreignId('ref_bale_id')->nullable()->constrained('bales')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancel_bales');
    }
};
