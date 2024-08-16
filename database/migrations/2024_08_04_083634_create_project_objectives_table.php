<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectObjectivesTable extends Migration
{
    public function up()
    {
        Schema::create('project_objectives', function (Blueprint $table) {
            $table->increments('id');
            $table->string('project_id')->index();
            $table->string('objective_id')->unique();
            $table->text('description');

            $table->timestamps();
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');

        });
    }

    public function down()
    {
        Schema::dropIfExists('project_objectives');
    }
}
