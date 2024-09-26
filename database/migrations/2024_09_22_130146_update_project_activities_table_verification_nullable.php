<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProjectActivitiesTableVerificationNullable extends Migration
{
    public function up()
    {
        Schema::table('project_activities', function (Blueprint $table) {
            // Modify the 'verification' column to allow null values
            $table->text('verification')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('project_activities', function (Blueprint $table) {
            // Revert the 'verification' column to be non-nullable
            $table->text('verification')->nullable(false)->change();
        });
    }
}
