<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectCicBasicInfoTable extends Migration
{
    public function up()
    {
        Schema::create('project_cic_basic_info', function (Blueprint $table) {
            $table->id();
            $table->string('cic_basic_info_id')->unique();
            $table->string('project_id');
            $table->integer('number_served_since_inception')->nullable();
            $table->integer('number_served_previous_year')->nullable();
            $table->text('beneficiary_categories')->nullable();
            $table->text('sisters_intervention')->nullable();
            $table->text('beneficiary_conditions')->nullable();
            $table->text('beneficiary_problems')->nullable();
            $table->text('institution_challenges')->nullable();
            $table->text('support_received')->nullable();
            $table->text('project_need')->nullable();
            $table->timestamps();

            // Foreign key constraint (optional, if you have a projects table)
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_cic_basic_info');
    }
}
