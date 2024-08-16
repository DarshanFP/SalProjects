<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateProjectActivitiesTable extends Migration
{
    public function up()
    {
        // Use raw SQL to rename the column
        DB::statement('ALTER TABLE project_activities CHANGE description activity TEXT');
    }

    public function down()
    {
        // Use raw SQL to revert the column name back
        DB::statement('ALTER TABLE project_activities CHANGE activity description TEXT');
    }
}
