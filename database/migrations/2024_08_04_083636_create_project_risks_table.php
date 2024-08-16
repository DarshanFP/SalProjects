<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectRisksTable extends Migration
{
    public function up()
    {
        Schema::create('project_risks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('risk_id')->unique();
            $table->string('objective_id')->index();
            $table->text('description');
            $table->timestamps();

            $table->foreign('objective_id')->references('objective_id')->on('project_objectives')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_risks');
    }
}
