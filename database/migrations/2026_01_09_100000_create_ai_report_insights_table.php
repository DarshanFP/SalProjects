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
        Schema::create('ai_report_insights', function (Blueprint $table) {
            $table->id();

            // Report identification
            $table->enum('report_type', ['quarterly', 'half_yearly', 'annual']);
            $table->string('report_id')->comment('Report ID (string) from quarterly_reports, half_yearly_reports, or annual_reports');

            // Core AI Content (all report types)
            $table->text('executive_summary')->nullable()->comment('2-5 paragraph executive summary');
            $table->json('key_achievements')->nullable()->comment('Array of achievement objects');
            $table->json('progress_trends')->nullable()->comment('Trends analysis object');
            $table->json('challenges')->nullable()->comment('Array of challenge objects');
            $table->json('recommendations')->nullable()->comment('Array of recommendation objects');

            // Half-Yearly & Annual Specific
            $table->json('strategic_insights')->nullable()->comment('Strategic insights array');
            $table->json('quarterly_comparison')->nullable()->comment('Q1 vs Q2 comparison (half-yearly only)');

            // Annual Specific Only
            $table->json('impact_assessment')->nullable()->comment('Impact assessment object');
            $table->json('budget_performance')->nullable()->comment('Budget performance analysis');
            $table->json('future_outlook')->nullable()->comment('Future outlook and projections');
            $table->json('year_over_year_comparison')->nullable()->comment('Year-over-year comparison');

            // AI Metadata
            $table->string('ai_model_used')->nullable()->comment('e.g., gpt-4o-mini');
            $table->integer('ai_tokens_used')->nullable();
            $table->timestamp('generated_at')->nullable();

            // Edit Tracking
            $table->timestamp('last_edited_at')->nullable()->comment('When user last edited AI content');
            $table->foreignId('last_edited_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_edited')->default(false)->comment('Whether AI content has been manually edited');

            $table->timestamps();

            // Indexes
            $table->unique(['report_type', 'report_id'], 'unique_report_insight');
            $table->index('report_type');
            $table->index('report_id');
            $table->index('generated_at');
            $table->index('is_edited');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_report_insights');
    }
};
