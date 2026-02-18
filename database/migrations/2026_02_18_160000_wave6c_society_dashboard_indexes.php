<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wave 6C: Composite indexes for society-wise dashboard aggregations.
     * - projects: (province_id, society_id) for approved/pending totals by society.
     * - DP_Reports: (province_id, society_id) for reported totals by society.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['province_id', 'society_id'], 'projects_province_society_index');
        });

        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->index(['province_id', 'society_id'], 'DP_Reports_province_society_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_province_society_index');
        });

        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->dropIndex('DP_Reports_province_society_index');
        });
    }
};