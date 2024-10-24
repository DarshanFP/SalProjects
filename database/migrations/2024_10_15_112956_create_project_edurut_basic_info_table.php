<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectEduRUTBasicInfoTable extends Migration
{
    public function up()
    {
        Schema::create('Project_EduRUT_Basic_Info', function (Blueprint $table) {
            $table->id();
            $table->string('operational_area_id')->unique();
            $table->string('project_id');
            $table->string('institution_type')->nullable(); // Institutional / Non-Institutional
            $table->string('group_type')->nullable(); // CHILDREN / YOUTH
            $table->string('category')->nullable(); // Rural, Urban, Tribal
            $table->text('project_location')->nullable();
            $table->text('sisters_work')->nullable();
            $table->text('conditions')->nullable(); // Socio-economic conditions
            $table->text('problems')->nullable(); // Identified problems
            $table->text('need')->nullable(); // Need of the project
            $table->text('criteria')->nullable(); // Criteria for selecting the target group
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('Project_EduRUT_Basic_Info');
    }
}
