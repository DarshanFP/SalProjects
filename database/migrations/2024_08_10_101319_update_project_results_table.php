<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateProjectResultsTable extends Migration
{
    public function up()
    {
        // Use raw SQL to rename the column
        DB::statement('ALTER TABLE project_results CHANGE outcome result TEXT');
    }

    public function down()
    {
        // Use raw SQL to revert the column name back
        DB::statement('ALTER TABLE project_results CHANGE result outcome TEXT');
    }
}
