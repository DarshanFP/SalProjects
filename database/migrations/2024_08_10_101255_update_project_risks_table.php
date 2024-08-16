<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateProjectRisksTable extends Migration
{
    public function up()
    {
        // Use raw SQL to rename the column
        DB::statement('ALTER TABLE project_risks CHANGE description risk TEXT');
    }

    public function down()
    {
        // Use raw SQL to revert the column name back
        DB::statement('ALTER TABLE project_risks CHANGE risk description TEXT');
    }
}
