<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqstActivitiesTable extends Migration
{
    public function up()
    {
        Schema::create('rqst_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('rqst_objectives')->onDelete('cascade');
            $table->string('month')->nullable();
            $table->text('summary_activities')->nullable();
            $table->text('qualitative_quantitative_data')->nullable();
            $table->text('intermediate_outcomes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqst_activities');
    }
}
