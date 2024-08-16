<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectSustainabilitiesTable extends Migration
{
    public function up()
    {
        Schema::create('project_sustainabilities', function (Blueprint $table) {
            $table->id();
            $table->string('sustainability_id')->unique();
            $table->string('project_id');
            $table->text('sustainability')->nullable();
            $table->text('monitoring_process')->nullable();
            $table->text('reporting_methodology')->nullable();
            $table->text('evaluation_methodology')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_sustainabilities');
    }
}
