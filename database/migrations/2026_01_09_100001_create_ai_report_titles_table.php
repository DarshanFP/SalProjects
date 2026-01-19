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
        Schema::create('ai_report_titles', function (Blueprint $table) {
            $table->id();

            // Report identification
            $table->enum('report_type', ['quarterly', 'half_yearly', 'annual']);
            $table->string('report_id')->comment('Report ID (string) from quarterly_reports, half_yearly_reports, or annual_reports');

            // Titles
            $table->string('report_title')->nullable()->comment('AI-generated report title');
            $table->json('section_headings')->nullable()->comment('Key-value pairs of section headings');

            // AI Metadata
            $table->string('ai_model_used')->nullable();
            $table->integer('ai_tokens_used')->nullable();
            $table->timestamp('generated_at')->nullable();

            // Edit Tracking
            $table->timestamp('last_edited_at')->nullable();
            $table->foreignId('last_edited_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_edited')->default(false);

            $table->timestamps();

            // Indexes
            $table->unique(['report_type', 'report_id'], 'unique_report_title');
            $table->index('report_type');
            $table->index('report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_report_titles');
    }
};
