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
        Schema::create('half_yearly_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_id')->unique(); // e.g., 'HY-2025-H1-DP-0001'
            $table->string('project_id');
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');

            // Half-year information
            $table->tinyInteger('half_year')->comment('1 or 2');
            $table->year('year');
            $table->date('period_from');
            $table->date('period_to');

            // Report data (similar to monthly reports)
            $table->string('project_title')->nullable();
            $table->string('project_type')->nullable();
            $table->string('place')->nullable();
            $table->string('society_name')->nullable();
            $table->date('commencement_month_year')->nullable();
            $table->string('in_charge')->nullable();
            $table->integer('total_beneficiaries')->nullable();
            $table->text('goal')->nullable();

            // Account period
            $table->date('account_period_start')->nullable();
            $table->date('account_period_end')->nullable();
            $table->decimal('amount_sanctioned_overview', 15, 2)->nullable()->default(0.00);
            $table->decimal('amount_forwarded_overview', 15, 2)->nullable()->default(0.00);
            $table->decimal('amount_in_hand', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_balance_forwarded', 15, 2)->nullable()->default(0.00);

            // Status and generation info
            $table->string('status')->default('draft');
            $table->text('revert_reason')->nullable();
            $table->json('generated_from')->nullable()->comment('Array of quarterly/monthly report IDs used to generate this report');
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('project_id');
            $table->index('half_year');
            $table->index('year');
            $table->index(['half_year', 'year']);
            $table->index('status');
            $table->index('generated_by_user_id');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('half_yearly_reports');
    }
};
