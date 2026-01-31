<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6: Admin Budget Reconciliation – immutable audit log.
 * Every admin correction (accept_suggested / manual_correction / reject) is recorded.
 *
 * @see Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md §10 Phase 6a
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_correction_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->comment('projects.id');
            $table->string('project_type', 100)->nullable();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('user_role', 50)->nullable();
            $table->string('action_type', 50)->comment('accept_suggested | manual_correction | reject');
            $table->decimal('old_overall', 15, 2)->nullable();
            $table->decimal('old_forwarded', 15, 2)->nullable();
            $table->decimal('old_local', 15, 2)->nullable();
            $table->decimal('old_sanctioned', 15, 2)->nullable();
            $table->decimal('old_opening', 15, 2)->nullable();
            $table->decimal('new_overall', 15, 2)->nullable();
            $table->decimal('new_forwarded', 15, 2)->nullable();
            $table->decimal('new_local', 15, 2)->nullable();
            $table->decimal('new_sanctioned', 15, 2)->nullable();
            $table->decimal('new_opening', 15, 2)->nullable();
            $table->text('admin_comment')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['admin_user_id', 'created_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_correction_audit');
    }
};
