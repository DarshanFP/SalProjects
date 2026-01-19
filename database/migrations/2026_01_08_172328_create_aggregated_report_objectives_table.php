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
        Schema::create('aggregated_report_objectives', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to support quarterly, half-yearly, and annual reports
            $table->string('report_type')->comment('quarterly, half_yearly, or annual');
            $table->unsignedBigInteger('report_id')->comment('ID from quarterly_reports, half_yearly_reports, or annual_reports');

            // Objective data
            $table->text('objective_text')->nullable();
            $table->text('cumulative_progress')->nullable()->comment('Aggregated progress across the period');
            $table->json('monthly_breakdown')->nullable()->comment('Monthly breakdown of progress');

            // Link to original project objective (optional)
            $table->unsignedBigInteger('project_objective_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['report_type', 'report_id']);
            $table->index('project_objective_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aggregated_report_objectives');
    }
};
