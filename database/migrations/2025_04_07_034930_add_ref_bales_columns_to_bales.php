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
        Schema::table('bales', function (Blueprint $table) {
            $table->foreignId('ref_packinglist_id')->nullable()->constrained('packinglists')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bales', function (Blueprint $table) {
            $table->dropColumn('ref_packinglist_id');
        });
    }
};
