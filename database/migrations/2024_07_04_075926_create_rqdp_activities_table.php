<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqdpActivitiesTable extends Migration
{
    public function up()
    {
        Schema::create('rqdp_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('rqdp_objectives')->onDelete('cascade');
            $table->date('month')->nullable();
            $table->text('summary_activities')->nullable();
            $table->text('qualitative_quantitative_data')->nullable();
            $table->text('intermediate_outcomes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqdp_activities');
    }
}
