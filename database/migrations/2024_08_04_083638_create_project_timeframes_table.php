<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTimeframesTable extends Migration
{
    public function up()
    {
        Schema::create('project_timeframes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('timeframe_id')->unique();
            $table->string('activity_id')->index();
            $table->string('month');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->foreign('activity_id')->references('activity_id')->on('project_activities')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_timeframes');
    }
}
