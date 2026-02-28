<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase D.4: Add indexes for project access queries.
 * projects.user_id and in_charge already indexed via FK; province_id indexed by add migration.
 * This adds index on status for status-filtered queries.
 */
class AddProjectAccessIndexes extends Migration
{
    public function up(): void
    {
        $indexExists = DB::select("SHOW INDEX FROM projects WHERE Key_name = 'projects_status_index'");
        if (empty($indexExists)) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index('status', 'projects_status_index');
            });
        }
    }

    public function down(): void
    {
        $indexExists = DB::select("SHOW INDEX FROM projects WHERE Key_name = 'projects_status_index'");
        if (!empty($indexExists)) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex('projects_status_index');
            });
        }
    }
}
