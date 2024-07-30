<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpActivitiesTable extends Migration
{
    public function up()
    {
        Schema::create('DP_Activities', function (Blueprint $table) {
            $table->id(); // Adds an auto-incrementing primary key
            $table->string('activity_id')->unique(); // Unique activity_id
            $table->string('objective_id'); // Ensure this matches the type in DP_Objectives
            $table->string('month')->nullable();
            $table->text('summary_activities')->nullable();
            $table->text('qualitative_quantitative_data')->nullable();
            $table->text('intermediate_outcomes')->nullable();
            $table->timestamps();

            $table->foreign('objective_id')->references('objective_id')->on('DP_Objectives')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('DP_Activities');
    }
}
