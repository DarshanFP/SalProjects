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
     * Add indexes to support executor project list queries at scale (10,000+ rows).
     * - user_id + status: owned projects filtered by status
     * - in_charge + status: in-charge projects filtered by status
     * - commencement_month_year: FY filter (inFinancialYear)
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index(['in_charge', 'status']);
            $table->index('commencement_month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['in_charge', 'status']);
            $table->dropIndex(['commencement_month_year']);
        });
    }
};
