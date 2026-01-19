<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy all existing project status history data to activity_histories
        DB::statement("
            INSERT INTO activity_histories (
                type,
                related_id,
                previous_status,
                new_status,
                changed_by_user_id,
                changed_by_user_role,
                changed_by_user_name,
                notes,
                created_at,
                updated_at
            )
            SELECT
                'project' as type,
                project_id as related_id,
                previous_status,
                new_status,
                changed_by_user_id,
                changed_by_user_role,
                changed_by_user_name,
                notes,
                created_at,
                updated_at
            FROM project_status_histories
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated data (only project type records)
        DB::table('activity_histories')
            ->where('type', 'project')
            ->delete();
    }
};
