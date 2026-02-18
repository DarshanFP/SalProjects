<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Wave 6A Phase 1: Add nullable society snapshot columns. No FK, no NOT NULL yet.
     */
    public function up(): void
    {
        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->unsignedBigInteger('society_id')->nullable()->after('project_id');
            $table->unsignedBigInteger('province_id')->nullable()->after('society_name');
            $table->index('society_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->dropIndex(['society_id']);
            $table->dropColumn(['society_id', 'province_id']);
        });
    }
};
