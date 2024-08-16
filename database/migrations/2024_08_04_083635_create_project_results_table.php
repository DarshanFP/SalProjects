<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectResultsTable extends Migration
{
    public function up()
    {
        Schema::create('project_results', function (Blueprint $table) {
            $table->increments('id');
            $table->string('result_id')->unique();
            $table->string('objective_id')->index();
            $table->text('outcome');
            $table->timestamps();

            $table->foreign('objective_id')->references('objective_id')->on('project_objectives')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_results');
    }
}
