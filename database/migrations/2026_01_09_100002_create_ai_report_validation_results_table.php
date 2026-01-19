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
        Schema::create('ai_report_validation_results', function (Blueprint $table) {
            $table->id();

            // Report identification
            $table->enum('report_type', ['monthly', 'quarterly', 'half_yearly', 'annual']);
            $table->string('report_id')->comment('Report ID (string) from respective table');

            // Validation Results
            $table->json('validation_results')->comment('Full validation structure');
            $table->enum('overall_status', ['ok', 'warning', 'error'])->default('ok');
            $table->integer('data_quality_score')->nullable()->comment('0-100 score');
            $table->string('overall_assessment')->nullable()->comment('excellent|good|fair|poor');

            // Counts for quick filtering
            $table->integer('inconsistencies_count')->default(0);
            $table->integer('missing_info_count')->default(0);
            $table->integer('unusual_patterns_count')->default(0);
            $table->integer('potential_errors_count')->default(0);

            // AI Metadata
            $table->string('ai_model_used')->nullable();
            $table->integer('ai_tokens_used')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['report_type', 'report_id'], 'unique_report_validation');
            $table->index('report_type');
            $table->index('report_id');
            $table->index('overall_status');
            $table->index('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_report_validation_results');
    }
};
