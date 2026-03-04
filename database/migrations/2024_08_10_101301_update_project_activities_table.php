<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateProjectActivitiesTable extends Migration
{
    public function up()
    {
        // Check if we're using SQLite (for testing)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        
        // Use raw SQL to rename the column (MySQL/MariaDB)
        DB::statement('ALTER TABLE project_activities CHANGE description activity TEXT');
    }

    public function down()
    {
        // Check if we're using SQLite (for testing)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        
        // Use raw SQL to revert the column name back
        DB::statement('ALTER TABLE project_activities CHANGE activity description TEXT');
    }
}
