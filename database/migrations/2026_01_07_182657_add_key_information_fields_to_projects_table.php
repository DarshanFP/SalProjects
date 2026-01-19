<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add new text area fields to Key Information section
     * Fields are added BEFORE the 'goal' column to maintain logical order
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add new fields before 'goal' column
            $table->text('initial_information')->nullable()->before('goal');
            $table->text('target_beneficiaries')->nullable()->before('goal');
            $table->text('general_situation')->nullable()->before('goal');
            $table->text('need_of_project')->nullable()->before('goal');
            // Note: 'goal' column already exists, so we're adding before it
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'initial_information',
                'target_beneficiaries',
                'general_situation',
                'need_of_project'
            ]);
        });
    }
};
