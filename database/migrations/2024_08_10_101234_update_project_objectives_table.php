<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateProjectObjectivesTable extends Migration
{
    public function up()
    {
        // Use raw SQL to rename the column
        DB::statement('ALTER TABLE project_objectives CHANGE description objective TEXT');
    }

    public function down()
    {
        // Use raw SQL to revert the column name back
        DB::statement('ALTER TABLE project_objectives CHANGE objective description TEXT');
    }
}
