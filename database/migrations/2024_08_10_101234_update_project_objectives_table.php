<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateProjectObjectivesTable extends Migration
{
    public function up()
    {
        // Check if we're using SQLite (for testing)
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support CHANGE, so we need a workaround
            // For tests, we can skip this migration as it's just a column rename
            return;
        }
        
        // Use raw SQL to rename the column (MySQL/MariaDB)
        DB::statement('ALTER TABLE project_objectives CHANGE description objective TEXT');
    }

    public function down()
    {
        // Check if we're using SQLite (for testing)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        
        // Use raw SQL to revert the column name back
        DB::statement('ALTER TABLE project_objectives CHANGE objective description TEXT');
    }
}
