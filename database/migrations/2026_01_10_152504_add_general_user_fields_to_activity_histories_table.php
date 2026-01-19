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
        Schema::table('activity_histories', function (Blueprint $table) {
            // Action type: status_change (default), draft_save, submit, update, comment
            $table->enum('action_type', ['status_change', 'draft_save', 'submit', 'update', 'comment'])
                  ->default('status_change')
                  ->after('new_status');

            // Approval context: 'coordinator', 'provincial', 'general' (for General user's dual-role actions)
            $table->string('approval_context', 50)->nullable()->after('notes');

            // Revert level: 'executor', 'applicant', 'provincial', 'coordinator' (for granular reverts)
            $table->string('revert_level', 50)->nullable()->after('approval_context');

            // Reverted to user ID (optional, for reverts to specific users)
            $table->unsignedBigInteger('reverted_to_user_id')->nullable()->after('changed_by_user_id');

            // Foreign key for reverted_to_user_id
            $table->foreign('reverted_to_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_histories', function (Blueprint $table) {
            $table->dropForeign(['reverted_to_user_id']);
            $table->dropColumn([
                'action_type',
                'approval_context',
                'revert_level',
                'reverted_to_user_id',
            ]);
        });
    }
};
