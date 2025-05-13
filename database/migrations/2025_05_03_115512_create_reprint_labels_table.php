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
        Schema::create('reprint_labels', function (Blueprint $table) {
            $table->id();
            $table->string('bale_no')->nullable();
            $table->foreignId('packing_list_id')->constrained('packing_lists')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('qc')->constrained('employees')->nullOnDelete();
            $table->foreignId('finalist')->constrained('employees')->nullOnDelete();
            $table->foreignId('ref_bale_id')->nullable()->constrained('bales')->nullOnDelete();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_printed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reprint_labels');
    }
};
