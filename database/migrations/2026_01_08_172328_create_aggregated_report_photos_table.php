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
        Schema::create('aggregated_report_photos', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to support quarterly, half-yearly, and annual reports
            $table->string('report_type')->comment('quarterly, half_yearly, or annual');
            $table->unsignedBigInteger('report_id')->comment('ID from quarterly_reports, half_yearly_reports, or annual_reports');

            // Photo data
            $table->string('photo_path');
            $table->text('description')->nullable();

            // Source tracking
            $table->string('source_monthly_report_id')->nullable()->comment('Link to original monthly report');
            $table->tinyInteger('source_month')->nullable()->comment('Month (1-12) from source report');
            $table->year('source_year')->nullable()->comment('Year from source report');

            $table->timestamps();

            // Foreign key to monthly reports (optional, for tracking)
            $table->foreign('source_monthly_report_id')
                  ->references('report_id')
                  ->on('DP_Reports')
                  ->onDelete('set null');

            // Indexes
            $table->index(['report_type', 'report_id']);
            $table->index('source_monthly_report_id');
            $table->index(['source_month', 'source_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aggregated_report_photos');
    }
};
