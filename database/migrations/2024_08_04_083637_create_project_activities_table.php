<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectActivitiesTable extends Migration
{
    public function up()
    {
        Schema::create('project_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('activity_id')->unique();
            $table->string('objective_id')->index();
            $table->text('description');
            $table->text('verification');
            $table->timestamps();

            $table->foreign('objective_id')->references('objective_id')->on('project_objectives')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_activities');
    }
}
