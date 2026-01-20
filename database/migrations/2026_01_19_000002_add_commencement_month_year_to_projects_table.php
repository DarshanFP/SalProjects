<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add commencement_month and commencement_year for coordinator approval.
     * commencement_month_year already exists; these allow separate storage when needed.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedTinyInteger('commencement_month')->nullable()->after('current_phase');
            $table->unsignedSmallInteger('commencement_year')->nullable()->after('commencement_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['commencement_month', 'commencement_year']);
        });
    }
};
